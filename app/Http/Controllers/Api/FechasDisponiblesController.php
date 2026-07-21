<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\FechaDisponible;

class FechasDisponiblesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // ── Criterio 2: slots de SPA agotados ─────────────────
        // Max 16 slots/día, cada reserva consume ceil(personas/5) slots.
        $slotsPorFechaReservas = DB::table('reservas')
            ->select(DB::raw('DATE(fecha_visita) as fecha'), DB::raw('SUM(CEIL(cantidad_personas / 5)) as slots'))
            ->groupBy(DB::raw('DATE(fecha_visita)'))
            ->get()
            ->keyBy('fecha')
            ->map(function ($r) { return (int) $r->slots; });

        $slotsPorFechaOrders = DB::table('woocommerce_orders')
            ->whereNull('reserva_id')
            ->where('procesado', 'pendiente')
            ->whereNotNull('cantidad_personas')
            ->whereNotNull('fecha_visita_wc')
            ->select(DB::raw('DATE(fecha_visita_wc) as fecha'), DB::raw('SUM(CEIL(cantidad_personas / 5)) as slots'))
            ->groupBy(DB::raw('DATE(fecha_visita_wc)'))
            ->get()
            ->keyBy('fecha')
            ->map(function ($r) { return (int) $r->slots; });

        $fechasSinTinaja = [];
        $todasFechasT = $slotsPorFechaReservas->keys()->merge($slotsPorFechaOrders->keys())->unique();
        foreach ($todasFechasT as $fecha) {
            $total = $slotsPorFechaReservas->get($fecha, 0) + $slotsPorFechaOrders->get($fecha, 0);
            if ($total >= 16) {
                $fechasSinTinaja[] = $fecha;
            }
        }


        // ── Criterio 3: ubicaciones agotadas ─────────────────────
        $totalUbicaciones = DB::table('ubicaciones')->count();

        $fechasSinUbicacion = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->select(DB::raw('DATE(reservas.fecha_visita) as fecha'))
            ->whereNotNull('visitas.id_ubicacion')
            ->groupBy(DB::raw('DATE(reservas.fecha_visita)'))
            ->havingRaw('COUNT(DISTINCT visitas.id_ubicacion) >= ?', [$totalUbicaciones])
            ->pluck('fecha')
            ->toArray();



        // ── Criterio 4: cupos por espacio_tipo agotados ───────────
        $fechasSinEspacio = [];
        $wcProductId = $request->query('wc_product_id');

        if ($wcProductId) {
            $programa = DB::table('programas')
                ->where('wc_product_id', $wcProductId)
                ->select('espacio_tipo')
                ->first();

            if ($programa && $programa->espacio_tipo) {
                $espacioTipo = $programa->espacio_tipo;
                $maxCupo     = config('woocommerce.wc_espacios.' . $espacioTipo, 0);

                if ($maxCupo > 0) {
                    // divisor > 0: terraza (6) y reposera (2) → ceil(personas/divisor) ubicaciones/reserva
                    // divisor = 0: estaciones → 1 ubicación por reserva (COUNT simple)
                    $divisor = (int) config('woocommerce.wc_personas_por_ubicacion.' . $espacioTipo, 0);

                    if ($divisor > 0) {
                        $exprReservas = DB::raw("SUM(CEIL(reservas.cantidad_personas / {$divisor})) as total");
                        $exprOrders   = DB::raw("SUM(CEIL(woocommerce_orders.cantidad_personas / {$divisor})) as total");
                    } else {
                        $exprReservas = DB::raw('COUNT(*) as total');
                        $exprOrders   = DB::raw('COUNT(*) as total');
                    }

                    $cuposPorFechaReservas = DB::table('reservas')
                        ->join('programas', 'reservas.id_programa', '=', 'programas.id')
                        ->where('programas.espacio_tipo', $espacioTipo)
                        ->select(DB::raw('DATE(reservas.fecha_visita) as fecha'), $exprReservas)
                        ->groupBy(DB::raw('DATE(reservas.fecha_visita)'))
                        ->get()
                        ->keyBy('fecha')
                        ->map(function ($r) { return (int) $r->total; });

                    $cuposPorFechaOrders = DB::table('woocommerce_orders')
                        ->join('programas', 'woocommerce_orders.wc_product_id', '=', 'programas.wc_product_id')
                        ->where('programas.espacio_tipo', $espacioTipo)
                        ->whereNull('woocommerce_orders.reserva_id')
                        ->where('woocommerce_orders.procesado', 'pendiente')
                        ->whereNotNull('woocommerce_orders.fecha_visita_wc')
                        ->when($divisor > 0, function ($q) {
                            return $q->whereNotNull('woocommerce_orders.cantidad_personas');
                        })
                        ->select(DB::raw('DATE(woocommerce_orders.fecha_visita_wc) as fecha'), $exprOrders)
                        ->groupBy(DB::raw('DATE(woocommerce_orders.fecha_visita_wc)'))
                        ->get()
                        ->keyBy('fecha')
                        ->map(function ($r) { return (int) $r->total; });

                    $todasFechasE = $cuposPorFechaReservas->keys()->merge($cuposPorFechaOrders->keys())->unique();
                    foreach ($todasFechasE as $fecha) {
                        $total = $cuposPorFechaReservas->get($fecha, 0) + $cuposPorFechaOrders->get($fecha, 0);
                        if ($total >= $maxCupo) {
                            $fechasSinEspacio[] = $fecha;
                        }
                    }
                }
            }
        }

        // ── Combinar todos los criterios bloqueantes ──────────────
        $bloqueadas = array_unique(array_merge($fechasSinUbicacion, $fechasSinTinaja, $fechasSinEspacio));

        $fechas = FechaDisponible::where('habilitada', true)
            ->where('fecha', '>=', now()->addDay()->toDateString())
            ->where('fecha', '<=', now()->addDays(120)->toDateString())
            ->whereNotIn('fecha', $bloqueadas)
            ->orderBy('fecha')
            ->pluck('fecha')
            ->map(function ($f) { return $f->format('Y-m-d'); })
            ->values();

        return response()->json([
            'success' => true,
            'fechas'  => $fechas,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
