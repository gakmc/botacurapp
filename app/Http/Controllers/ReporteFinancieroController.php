<?php

namespace App\Http\Controllers;

use App\Egreso;
use App\GiftCard;
use App\HonorarioBte;
use App\PagoEgreso;
use App\PoroPoroVenta;
use App\Programa;
use App\Reserva;
use App\TipoTransaccion;
use App\Venta;
use App\VentaDirecta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteFinancieroController extends Controller
{
    // ── EGRESOS: acumulado por categoria/subcategoria ─────────────────────────

    public function acumuladoEgresos(Request $request, $anio = null, $mes = null)
    {
        $anio = (int) ($anio ?? $request->input('anio', now()->year));
        $mes  = (int) ($mes  ?? $request->input('mes',  now()->month));

        $inicio    = Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
        $fin       = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();
        $mesNombre = Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM');

        $filas = DB::table('egresos')
            ->leftJoin('categorias_compras',    'egresos.categoria_id',    '=', 'categorias_compras.id')
            ->leftJoin('subcategorias_compras', 'egresos.subcategoria_id', '=', 'subcategorias_compras.id')
            ->leftJoin('proveedores',           'egresos.proveedor_id',    '=', 'proveedores.id')
            ->select(
                'egresos.id', 'egresos.fecha_egreso', 'egresos.descripcion',
                'egresos.numero_documento', 'egresos.neto', 'egresos.iva',
                'egresos.total', 'egresos.fuente', 'egresos.estado',
                DB::raw('COALESCE(categorias_compras.nombre, "Sin categoria") as categoria_nombre'),
                DB::raw('COALESCE(subcategorias_compras.nombre, "Sin subcategoria") as subcategoria_nombre'),
                DB::raw('proveedores.nombre as proveedor_nombre')
            )
            ->whereBetween('egresos.fecha_egreso', [$inicio, $fin])
            ->where('egresos.estado', '!=', 'anulado')
            ->orderBy('categorias_compras.nombre')
            ->orderBy('subcategorias_compras.nombre')
            ->orderBy('egresos.fecha_egreso')
            ->get();

        $agrupado = [];
        foreach ($filas as $fila) {
            $cat = $fila->categoria_nombre;
            $sub = $fila->subcategoria_nombre;
            $tot = (int) ($fila->total ?? $fila->neto ?? 0);
            if (!isset($agrupado[$cat])) {
                $agrupado[$cat] = ['total' => 0, 'subcategorias' => []];
            }
            if (!isset($agrupado[$cat]['subcategorias'][$sub])) {
                $agrupado[$cat]['subcategorias'][$sub] = ['total' => 0, 'filas' => []];
            }
            $agrupado[$cat]['total'] += $tot;
            $agrupado[$cat]['subcategorias'][$sub]['total'] += $tot;
            $agrupado[$cat]['subcategorias'][$sub]['filas'][] = $fila;
        }
        uasort($agrupado, function ($a, $b) { return $b['total'] - $a['total']; });

        $totalGeneral = array_sum(array_column($agrupado, 'total'));
        $periodo      = sprintf('%04d%02d', $anio, $mes);
        $totalBte     = (int) HonorarioBte::where('periodo', $periodo)->where('estado', '!=', 'Anulada')->sum('monto_pagado');
        $totalSueldos = (int) DB::table('sueldos_pagados')->whereBetween('fecha_pago', [$inicio, $fin])->sum('monto');

        $antInicio     = Carbon::create($anio, $mes, 1)->subMonth()->startOfMonth()->toDateString();
        $antFin        = Carbon::create($anio, $mes, 1)->subMonth()->endOfMonth()->toDateString();
        $totalAnterior = (int) DB::table('egresos')->whereBetween('fecha_egreso', [$antInicio, $antFin])->where('estado', '!=', 'anulado')->sum(DB::raw('COALESCE(total, neto, 0)'));

        $anterioresRaw = DB::table('egresos')
            ->leftJoin('categorias_compras', 'egresos.categoria_id', '=', 'categorias_compras.id')
            ->selectRaw('COALESCE(categorias_compras.nombre, "Sin categoria") as cat, SUM(COALESCE(egresos.total, egresos.neto, 0)) as total')
            ->whereBetween('egresos.fecha_egreso', [$antInicio, $antFin])
            ->where('egresos.estado', '!=', 'anulado')
            ->groupBy('cat')->get();

        $anteriores = [];
        foreach ($anterioresRaw as $r) { $anteriores[$r->cat] = (int) $r->total; }

        $anualRaw = DB::table('egresos')
            ->selectRaw('MONTH(fecha_egreso) as mn, SUM(COALESCE(total, neto, 0)) as total')
            ->whereYear('fecha_egreso', $anio)->where('estado', '!=', 'anulado')
            ->groupBy('mn')->pluck('total', 'mn');

        $resumenAnual = collect(range(1, 12))->mapWithKeys(function ($m) use ($anualRaw) {
            return [$m => (int) ($anualRaw[$m] ?? 0)];
        });

        $chartLabels = [];
        $chartData   = [];
        foreach ($agrupado as $cat => $datos) {
            if ($datos['total'] > 0) { $chartLabels[] = $cat; $chartData[] = $datos['total']; }
        }

        return view('themes.backoffice.pages.reporte.egresos.acumulado', compact(
            'anio', 'mes', 'mesNombre', 'agrupado', 'totalGeneral', 'totalBte', 'totalSueldos',
            'totalAnterior', 'anteriores', 'resumenAnual', 'chartLabels', 'chartData'
        ));
    }

    // ── RESUMEN ANUAL ─────────────────────────────────────────────────────────

    public function resumenAnual(Request $request)
    {
        $anio = $request->anio ?? now()->year;

        $abonos = DB::table('ventas')->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(ventas.abono_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)->groupBy('mes')->get();

        $diferencias = DB::table('ventas')->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(ventas.diferencia_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)->groupBy('mes')->get();

        $consumos = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(detalles_consumos.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)->groupBy('mes')->get();

        $servicios = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(detalle_servicios_extra.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)->groupBy('mes')->get();

        $egresos = DB::table('pagos_egresos')
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(COALESCE(monto,0)-COALESCE(iva,0)-COALESCE(impuesto_incluido,0)) as total')
            ->whereYear('fecha_pago', $anio)->groupBy('mes')->orderBy('mes')->get();

        $ventasDirectas = DB::table('ventas_directas')
            ->selectRaw('MONTH(fecha) as mes, SUM(subtotal) as total')
            ->whereYear('fecha', $anio)->groupBy('mes')->get();

        $sueldos = DB::table('sueldos_pagados')
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto) as total')
            ->whereYear('fecha_pago', $anio)->groupBy('mes')->get();

        $bonos = DB::table('sueldos_pagados')
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(bono) as total')
            ->whereYear('fecha_pago', $anio)->groupBy('mes')->get();

        $impuestos = DB::table('pagos_egresos')
            ->selectRaw('MONTH(fecha_pago) AS mes, SUM(COALESCE(iva,0)) AS total_iva, SUM(COALESCE(impuesto_incluido,0)) AS total_imp_adic, SUM(COALESCE(iva,0)+COALESCE(impuesto_incluido,0)) AS total')
            ->whereYear('fecha_pago', $anio)->groupBy('mes')->orderBy('mes')->get();

        return view('themes.backoffice.pages.reporte.financiero.anual', compact(
            'abonos', 'diferencias', 'consumos', 'servicios',
            'egresos', 'sueldos', 'bonos', 'impuestos', 'ventasDirectas', 'anio'
        ));
    }

    // ── RESUMEN MENSUAL ───────────────────────────────────────────────────────

    public function resumenMensual($anio, $mes)
    {
        $abonos = DB::table('ventas')->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita,1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(ventas.abono_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $diferencias = DB::table('ventas')->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita,1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(ventas.diferencia_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $consumos = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita,1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(detalles_consumos.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $servicios = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita,1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(detalle_servicios_extra.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $ini = Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $egresos = DB::table('pagos_egresos')
            ->selectRaw('YEARWEEK(fecha_pago,1) as yearweek, DATE(fecha_pago) as fecha, SUM(COALESCE(monto,0)-COALESCE(iva,0)-COALESCE(impuesto_incluido,0)) as total')
            ->whereBetween('fecha_pago', [$ini, $fin])->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $ventasDirectas = DB::table('ventas_directas')
            ->selectRaw('YEARWEEK(fecha,1) as yearweek, DATE(fecha) as fecha, SUM(subtotal) as total')
            ->whereBetween('fecha', [$ini, $fin])->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $sueldos = DB::table('sueldos_pagados')
            ->selectRaw('YEARWEEK(fecha_pago,1) as yearweek, DATE(fecha_pago) as fecha, SUM(monto) as total')
            ->whereBetween('fecha_pago', [$ini, $fin])->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $bonos = DB::table('sueldos_pagados')
            ->selectRaw('YEARWEEK(fecha_pago,1) as yearweek, DATE(fecha_pago) as fecha, SUM(bono) as total')
            ->whereBetween('fecha_pago', [$ini, $fin])->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $impuestos = DB::table('pagos_egresos')
            ->selectRaw('YEARWEEK(fecha_pago,1) as yearweek, DATE(fecha_pago) as fecha, SUM(COALESCE(iva,0)+COALESCE(impuesto_incluido,0)) as total')
            ->whereBetween('fecha_pago', [$ini, $fin])->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        setlocale(LC_TIME, 'es_ES.UTF-8');
        $mesNombre = Carbon::createFromDate($anio, $mes, 1)->translatedFormat('F');

        return view('themes.backoffice.pages.reporte.financiero.mensual', compact(
            'anio', 'mes', 'mesNombre',
            'abonos', 'diferencias', 'consumos', 'servicios',
            'egresos', 'sueldos', 'bonos', 'impuestos', 'ventasDirectas'
        ));
    }

    // ── INGRESOS PERCIBIDOS ───────────────────────────────────────────────────

    public function ingresosPercibidos(Request $request)
    {
        $anio = $request->input('anio', now()->year);
        $mes  = $request->input('mes',  now()->month);

        $abonos = DB::table('ventas')->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at,1) as yearweek, DATE(reservas.created_at) as fecha, SUM(ventas.abono_programa) as total')
            ->whereYear('reservas.created_at', $anio)->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $diferencias = DB::table('ventas')->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at,1) as yearweek, DATE(reservas.created_at) as fecha, SUM(ventas.diferencia_programa) as total')
            ->whereYear('reservas.created_at', $anio)->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $consumos = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at,1) as yearweek, DATE(reservas.created_at) as fecha, SUM(detalles_consumos.subtotal) as total')
            ->whereYear('reservas.created_at', $anio)->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $servicios = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at,1) as yearweek, DATE(reservas.created_at) as fecha, SUM(detalle_servicios_extra.subtotal) as total')
            ->whereYear('reservas.created_at', $anio)->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')->orderBy('fecha')->get();

        $ini = Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
        $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

        $ventasDirectas = DB::table('ventas_directas')
            ->selectRaw('YEARWEEK(created_at,1) as yearweek, DATE(created_at) as fecha, SUM(subtotal) as total')
            ->whereBetween('created_at', [$ini, $fin])->groupBy('yearweek', 'created_at')->orderBy('fecha')->get();

        setlocale(LC_TIME, 'es_ES.UTF-8');
        $mesNombre = Carbon::createFromDate($anio, $mes, 1)->translatedFormat('F');

        $ingresosVentas   = 0;
        $ventasPendientes = 0;
        $totalGc          = 0;

        $gcs = GiftCard::whereYear('created_at', $anio)->whereMonth('created_at', $mes)->get();
        foreach ($gcs as $gc) { $totalGc += $gc->monto; }
        $cantidadGc = count($gcs);

        $ventas = Venta::whereHas('reserva', function ($q) use ($mes, $anio) {
            $q->whereMonth('created_at', $mes)->whereYear('created_at', $anio);
        })->with('reserva.cliente', 'reserva.programa', 'consumo.detallesConsumos', 'consumo.detalleServiciosExtra')->paginate(20);

        foreach ($ventas as $venta) {
            $venta->pagado_con_giftcard = GiftCard::where('id_venta', $venta->id)->exists();
            if (!$venta->pagado_con_giftcard) {
                $ingresosVentas   += $venta->abono_programa;
                $ingresosVentas   += $venta->diferencia_programa;
                $ventasPendientes += $venta->total_pagar;
            }
        }

        $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes) {
            $abono = Venta::where('id_tipo_transaccion_abono', $tipo->id)
                ->whereHas('reserva', function ($q) use ($anio, $mes) {
                    $q->whereYear('created_at', $anio)->whereMonth('created_at', $mes);
                })->sum('abono_programa');

            $pago1 = \App\PagoConsumo::where('id_tipo_transaccion1', $tipo->id)
                ->whereHas('venta.reserva', function ($q) use ($anio, $mes) {
                    $q->whereYear('created_at', $anio)->whereMonth('created_at', $mes);
                })->sum('pago1');

            $pago2 = \App\PagoConsumo::where('id_tipo_transaccion2', $tipo->id)->whereNotNull('pago2')
                ->whereHas('venta.reserva', function ($q) use ($anio, $mes) {
                    $q->whereYear('created_at', $anio)->whereMonth('created_at', $mes);
                })->sum('pago2');

            $tipo->total_abonos      = $abono;
            $tipo->total_diferencias = $pago1 + $pago2;
            $tipo->venta_directa     = VentaDirecta::where('id_tipo_transaccion', $tipo->id)->whereYear('created_at', $anio)->whereMonth('created_at', $mes)->sum('subtotal');
            $tipo->poro              = PoroPoroVenta::where('id_tipo_transaccion', $tipo->id)->whereYear('created_at', $anio)->whereMonth('created_at', $mes)->sum('total');
            return $tipo;
        });

        $programas = Programa::all()->map(function ($programa) use ($mes, $anio) {
            $programa->total_programas = Reserva::where('id_programa', $programa->id)->whereMonth('created_at', $mes)->whereYear('created_at', $anio)->count();
            return $programa;
        });

        $fuentes = collect([])
            ->merge(DB::table('reservas')->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')->groupBy('anio', 'mes')->get())
            ->merge(DB::table('ventas_directas')->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')->groupBy('anio', 'mes')->get())
            ->merge(DB::table('gift_cards')->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')->groupBy('anio', 'mes')->get())
            ->merge(DB::table('poro_poro_ventas')->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')->groupBy('anio', 'mes')->get());

        $fechasDisponibles = $fuentes
            ->unique(function ($r) { return sprintf('%04d-%02d', $r->anio, $r->mes); })
            ->sortByDesc(function ($r) { return $r->anio * 100 + $r->mes; })
            ->values();

        return view('themes.backoffice.pages.reporte.ingreso.percibido', compact(
            'anio', 'mes', 'mesNombre',
            'abonos', 'diferencias', 'consumos', 'servicios',
            'ventasDirectas', 'programas', 'tiposTransacciones', 'ventas', 'fechasDisponibles'
        ));
    }

    // ── COMPARAR ─────────────────────────────────────────────────────────────

    public function comparar(Request $request)
    {
        try {
            $raw   = $request->input('meses', []);
            $meses = collect(is_array($raw) ? $raw : [$raw])
                ->filter()->map(function ($s) { return trim((string) $s); })
                ->unique()->values()->take(4);

            if ($meses->isEmpty()) { return response('Faltan meses', 422); }

            $rows = $meses->map(function ($mmYYYY) {
                $parts = explode('-', $mmYYYY);
                if (count($parts) !== 2) { return null; }
                $mm = (int) $parts[0];
                $yy = (int) $parts[1];
                if ($mm < 1 || $mm > 12 || $yy < 2000) { return null; }
                $desde = Carbon::create($yy, $mm, 1)->startOfMonth();
                $hasta = Carbon::create($yy, $mm, 1)->endOfMonth();
                return [
                    'label' => ucfirst(Carbon::create()->month($mm)->locale('es')->isoFormat('MMMM')) . ' ' . $yy,
                    'mes'   => $mm,
                    'anio'  => $yy,
                    'total' => (int) $this->totalIngresosPeriodo($desde, $hasta),
                ];
            })->filter()->values();

            $rows = $rows->sortBy(function ($r) { return ($r['anio'] * 100) + $r['mes']; })->values();

            $viewPath = 'themes.backoffice.pages.reporte.ingreso.partials._comparativa_ingresos';
            if (!view()->exists($viewPath)) { return response('Parcial no encontrado', 500); }

            return response(view($viewPath, compact('rows'))->render(), 200)->header('Content-Type', 'text/html');

        } catch (\Throwable $e) {
            Log::error('FinanzasCompararError', ['msg' => $e->getMessage()]);
            return response('Error interno', 500);
        }
    }

    // ── HELPER ────────────────────────────────────────────────────────────────

    protected function totalIngresosPeriodo(Carbon $desde, Carbon $hasta)
    {
        $v = DB::table('ventas')->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereBetween('reservas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(ventas.abono_programa,0)+COALESCE(ventas.diferencia_programa,0)'));

        $c = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereBetween('reservas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(detalles_consumos.subtotal,0)'));

        $s = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereBetween('reservas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(detalle_servicios_extra.subtotal,0)'));

        $d = DB::table('ventas_directas')
            ->whereBetween('ventas_directas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(ventas_directas.subtotal,0)'));

        return (int) ($v + $c + $s + $d);
    }
}
