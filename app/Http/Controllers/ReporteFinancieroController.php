<?php

namespace App\Http\Controllers;

use App\Egreso;
use App\GiftCard;
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
use Illuminate\Support\Facades\Schema;

class ReporteFinancieroController extends Controller
{
    public function resumenAnual(Request $request)
    {

        $anio = $request->anio ?? now()->year;


        // Ingresos
        $abonos = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(ventas.abono_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->groupBy('mes')
            ->get();

        $diferencias = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(ventas.diferencia_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->groupBy('mes')
            ->get();

        $consumos = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(detalles_consumos.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->groupBy('mes')
            ->get();

        $servicios = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('MONTH(reservas.fecha_visita) as mes, SUM(detalle_servicios_extra.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->groupBy('mes')
            ->get();


        // $egresos = Egreso::selectRaw('MONTH(fecha) as mes, SUM(COALESCE(total, 0) - COALESCE(iva, 0) - COALESCE(impuesto_incluido, 0)) as total')
        //     ->whereYear('fecha', $anio)
        //     ->groupBy('mes')
        //     ->get();

        $egresos = DB::table('pagos_egresos')
        ->selectRaw('
            MONTH(fecha_pago) as mes,
            SUM(COALESCE(monto, 0) - COALESCE(iva, 0) - COALESCE(impuesto_incluido, 0)) as total
        ')
        ->whereYear('fecha_pago', $anio)
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();



        $ventasDirectas = DB::table('ventas_directas')
            ->selectRaw('MONTH(fecha) as mes, SUM(subtotal) as total')
            ->whereYear('fecha', $anio)
            ->groupBy('mes')
            ->get();

        $sueldos = DB::table('sueldos_pagados')
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(monto) as total')
            ->whereYear('fecha_pago', $anio)
            ->groupBy('mes')
            ->get();

                // BONOS
        $bonos = DB::table('sueldos_pagados')
            ->selectRaw('MONTH(fecha_pago) as mes, SUM(bono) as total')
            ->whereYear('fecha_pago', $anio)
            ->groupBy('mes')
            ->get();

        // $impuestos = DB::table('egresos')
        //     ->selectRaw('MONTH(fecha) as mes, SUM(COALESCE(iva, 0) + COALESCE(impuesto_incluido, 0)) as total')
        //     ->whereYear('fecha', $anio)
        //     ->groupBy('mes')
        //     ->get();

        $impuestos = DB::table('pagos_egresos')
        ->selectRaw('
            MONTH(fecha_pago) AS mes,
            SUM(COALESCE(iva, 0))                 AS total_iva,
            SUM(COALESCE(impuesto_incluido, 0))   AS total_imp_adic,
            SUM(COALESCE(iva, 0) + COALESCE(impuesto_incluido, 0)) AS total
        ')
        ->whereYear('fecha_pago', $anio)
        ->groupBy('mes')
        ->orderBy('mes')
        ->get();


        // dd($impuestos, $egresos);

        return view('themes.backoffice.pages.reporte.financiero.anual', compact(
            'abonos', 'diferencias', 'consumos', 'servicios',
            'egresos', 'sueldos', 'bonos', 'impuestos', 'ventasDirectas', 'anio'
        ));
    }

    public function resumenMensual($anio,$mes)
    {
        // ABONOS
        $abonos = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita, 1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(ventas.abono_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();


        // DIFERENCIAS
        $diferencias = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita, 1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(ventas.diferencia_programa) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

        // CONSUMOS
        $consumos = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita, 1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(detalles_consumos.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

            
            // SERVICIOS
        $servicios = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.fecha_visita, 1) as yearweek, DATE(reservas.fecha_visita) as fecha, SUM(detalle_servicios_extra.subtotal) as total')
            ->whereYear('reservas.fecha_visita', $anio)
            ->whereMonth('reservas.fecha_visita', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();
            
            $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
            $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();
            
        $egresos = DB::table('pagos_egresos')
            ->selectRaw('YEARWEEK(fecha_pago, 1) as yearweek, DATE(fecha_pago) as fecha, SUM(COALESCE(monto, 0) - COALESCE(iva, 0) - COALESCE(impuesto_incluido, 0)) as total')
            ->whereBetween('fecha_pago', [$inicioMes, $finMes])
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();


        $ventasDirectas = DB::table('ventas_directas')
            ->selectRaw('YEARWEEK(fecha, 1) as yearweek, DATE(fecha) as fecha, SUM(subtotal) as total')
            ->whereBetween('fecha', [$inicioMes, $finMes])
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

        // SUELDOS
        $sueldos = DB::table('sueldos_pagados')
            ->selectRaw('YEARWEEK(fecha_pago, 1) as yearweek, DATE(fecha_pago) as fecha, SUM(monto) as total')
            ->whereBetween('fecha_pago', [$inicioMes, $finMes])
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

        // BONOS
        $bonos = DB::table('sueldos_pagados')
            ->selectRaw('YEARWEEK(fecha_pago, 1) as yearweek, DATE(fecha_pago) as fecha, SUM(bono) as total')
            ->whereBetween('fecha_pago', [$inicioMes, $finMes])
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

        // IMPUESTOS
        $impuestos = DB::table('pagos_egresos')
            ->selectRaw('YEARWEEK(fecha_pago, 1) as yearweek, DATE(fecha_pago) as fecha, SUM(COALESCE(iva, 0) + COALESCE(impuesto_incluido, 0)) as total')
            ->whereBetween('fecha_pago', [$inicioMes, $finMes])
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

        // Para mostrar nombre del mes
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $mesNombre = Carbon::createFromDate($anio, $mes, 1)->translatedFormat('F');

        // dd($impuestos);
        return view('themes.backoffice.pages.reporte.financiero.mensual', compact(
            'anio', 'mes', 'mesNombre',
            'abonos', 'diferencias', 'consumos', 'servicios',
            'egresos', 'sueldos', 'bonos', 'impuestos', 'ventasDirectas'
        ));
    }

    public function ingresosPercibidos(Request $request)
    {

        $anio = $request->input('anio',now()->year);
        $mes = $request->input('mes',now()->month);

        // dd($anio, $mes);
        // ABONOS
        $abonos = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at, 1) as yearweek, DATE(reservas.created_at) as fecha, SUM(ventas.abono_programa) as total')
            ->whereYear('reservas.created_at', $anio)
            ->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

        // DIFERENCIAS
        $diferencias = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at, 1) as yearweek, DATE(reservas.created_at) as fecha, SUM(ventas.diferencia_programa) as total')
            ->whereYear('reservas.created_at', $anio)
            ->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

        // CONSUMOS
        $consumos = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at, 1) as yearweek, DATE(reservas.created_at) as fecha, SUM(detalles_consumos.subtotal) as total')
            ->whereYear('reservas.created_at', $anio)
            ->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();

            
        // SERVICIOS
        $servicios = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->selectRaw('YEARWEEK(reservas.created_at, 1) as yearweek, DATE(reservas.created_at) as fecha, SUM(detalle_servicios_extra.subtotal) as total')
            ->whereYear('reservas.created_at', $anio)
            ->whereMonth('reservas.created_at', $mes)
            ->groupBy('yearweek', 'fecha')
            ->orderBy('fecha')
            ->get();
            
            $inicioMes = Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
            $finMes = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();
            


        $ventasDirectas = DB::table('ventas_directas')
            ->selectRaw('YEARWEEK(created_at, 1) as yearweek, DATE(created_at) as fecha, SUM(subtotal) as total')
            ->whereBetween('created_at', [$inicioMes, $finMes])
            ->groupBy('yearweek', 'created_at')
            ->orderBy('fecha')
            ->get();


        // Para mostrar nombre del mes
        setlocale(LC_TIME, 'es_ES.UTF-8');
        $mesNombre = Carbon::createFromDate($anio, $mes, 1)->translatedFormat('F');






        $ingresosVentas   = 0;
        $ventasPendientes = 0;
        $totalGc          = 0;

        $gcs = GiftCard::whereYear('created_at', $anio)
            ->whereMonth('created_at', $mes)
            ->get();

        foreach ($gcs as $gc) {
            $totalGc += $gc->monto;
        }

        $cantidadGc = COUNT($gcs) ?? 0;

        $ventas = Venta::whereHas('reserva', function ($query) use ($mes, $anio) {
            $query->whereMonth('created_at', $mes)
                ->whereYear('created_at', $anio);

        })->with('reserva.cliente', 'reserva.programa', 'consumo.detallesConsumos', 'consumo.detalleServiciosExtra')->paginate(20);

        // Marcar si cada venta fue pagada con GiftCard
        foreach ($ventas as $venta) {
            $venta->pagado_con_giftcard = GiftCard::where('id_venta', $venta->id)->exists();
            // Evitar sumar abono/diferencia si es con giftcard
            if (! $venta->pagado_con_giftcard) {
                $ingresosVentas += $venta->abono_programa;
                $ingresosVentas += $venta->diferencia_programa;
                $ventasPendientes += $venta->total_pagar;
            }
        }

        $tiposTransacciones = TipoTransaccion::all()->map(function ($tipo) use ($anio, $mes) {
            $abono = Venta::where('id_tipo_transaccion_abono', $tipo->id)
                ->whereHas('reserva', function ($query) use ($anio, $mes) {
                    $query->whereYear('created_at', $anio)
                        ->whereMonth('created_at', $mes);
                })
                ->sum('abono_programa');

            $total_pago1 = \App\PagoConsumo::where('id_tipo_transaccion1', $tipo->id)
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes) {
                    $query->whereYear('created_at', $anio)
                        ->whereMonth('created_at', $mes);
                })
                ->sum('pago1');

            $total_pago2 = \App\PagoConsumo::where('id_tipo_transaccion2', $tipo->id)
                ->whereNotNull('pago2')
                ->whereHas('venta.reserva', function ($query) use ($anio, $mes) {
                    $query->whereYear('created_at', $anio)
                        ->whereMonth('created_at', $mes);
                })
                ->sum('pago2');

            $ventaDirecta = VentaDirecta::where('id_tipo_transaccion', $tipo->id)
                ->whereYear('created_at', $anio)
                ->whereMonth('created_at', $mes)
                ->sum('subtotal');

            $poroPoro = PoroPoroVenta::where('id_tipo_transaccion', $tipo->id)
                ->whereYear('created_at', $anio)
                ->whereMonth('created_at', $mes)
                ->sum('total');

            $tipo->total_abonos      = $abono;
            $tipo->total_diferencias = $total_pago1 + $total_pago2;
            $tipo->venta_directa     = $ventaDirecta;
            $tipo->poro              = $poroPoro;

            return $tipo;
        });

        $programas = Programa::all()->map(function ($programa) use ($mes, $anio) {
            $cuenta = Reserva::where('id_programa', $programa->id)
                ->whereMonth('created_at', $mes)
                ->whereYear('created_at', $anio)
                ->count();

            $programa->total_programas = $cuenta;
            return $programa;
        });



        // $fechasDisponibles = Reserva::selectRaw('MONTH(created_at) as mes, YEAR(created_at) as anio')
        //     ->groupBy('mes', 'anio')
        //     ->orderBy('anio', 'desc')
        //     ->orderBy('mes', 'desc')
        //     ->get();




        
        $fuentes = collect([]);

        // Reservas (ingresos vía ventas/consumos/servicios)
        $fuentes = $fuentes->merge(
            DB::table('reservas')
                ->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')
                ->groupBy('anio','mes')
                ->get()
        );

        // Ventas directas
        $fuentes = $fuentes->merge(
            DB::table('ventas_directas')
                ->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')
                ->groupBy('anio','mes')
                ->get()
        );

        // GiftCards (si quieres mostrarlas como mes seleccionable)
        $fuentes = $fuentes->merge(
            DB::table('gift_cards')
                ->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')
                ->groupBy('anio','mes')
                ->get()
        );

        // Poro Poro (si corresponde)
        $fuentes = $fuentes->merge(
            DB::table('poro_poro_ventas')
                ->selectRaw('YEAR(created_at) as anio, MONTH(created_at) as mes')
                ->groupBy('anio','mes')
                ->get()
        );

        // Unificar, quitar duplicados y ordenar desc por año-mes
        $fechasDisponibles = $fuentes
            ->unique(function($r){ return sprintf('%04d-%02d', $r->anio, $r->mes); })
            ->sortByDesc(function($r){ return $r->anio * 100 + $r->mes; })
            ->values();



        // dd($impuestos);
        return view('themes.backoffice.pages.reporte.ingreso.percibido', compact(
            'anio', 'mes', 'mesNombre',
            'abonos', 'diferencias', 'consumos', 'servicios'
            , 'ventasDirectas', 'programas', 'tiposTransacciones', 'ventas', 'fechasDisponibles'
        ));
    }


    // ─────────────────────────────────────────────────────────────────────────
    // UTILIDAD — Ingresos vs Egresos SII + Honorarios + PPM
    // ─────────────────────────────────────────────────────────────────────────
    public function utilidad(Request $request)
    {
        $anio = (int) $request->input('anio', now()->year);
        $mes  = (int) $request->input('mes',  now()->month);

        $inicio    = Carbon::create($anio, $mes, 1)->startOfMonth();
        $fin       = Carbon::create($anio, $mes, 1)->endOfMonth();
        $nombreMes = $inicio->locale('es')->isoFormat('MMMM');
        $periodo   = sprintf('%04d%02d', $anio, $mes);
        $periodoKey = $anio . '-' . sprintf('%02d', $mes);

        $mesesNombres = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo',  6 => 'Junio',   7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        // ── Ingresos del mes ──────────────────────────────────────────────────
        $abonos = (int) DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereYear('reservas.fecha_visita', $anio)
            ->whereMonth('reservas.fecha_visita', $mes)
            ->sum(DB::raw('ventas.abono_programa + ventas.diferencia_programa'));

        $consumos = (int) DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereYear('reservas.fecha_visita', $anio)
            ->whereMonth('reservas.fecha_visita', $mes)
            ->sum('detalles_consumos.subtotal');

        $servicios = (int) DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereYear('reservas.fecha_visita', $anio)
            ->whereMonth('reservas.fecha_visita', $mes)
            ->sum('detalle_servicios_extra.subtotal');

        $directas = (int) DB::table('ventas_directas')
            ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
            ->sum('subtotal');

        $poro = Schema::hasTable('poro_poro_ventas')
            ? (int) DB::table('poro_poro_ventas')
                ->whereBetween('fecha', [$inicio->toDateString(), $fin->toDateString()])
                ->sum('total')
            : 0;

        $totalIngresos = $abonos + $consumos + $servicios + $directas + $poro;

        // ── Egresos del mes ───────────────────────────────────────────────────
        // 1. Facturas SII (fuente=sii, neto = monto sin IVA)
        $facturasSii = (int) DB::table('egresos')
            ->where('fuente', 'sii')
            ->where('periodo_sii', $periodoKey)
            ->sum('neto');

        // Fallback: si no hay columna neto, usar total
        if ($facturasSii === 0) {
            $facturasSii = (int) DB::table('egresos')
                ->where('fuente', 'sii')
                ->where('periodo_sii', $periodoKey)
                ->sum('total');
        }

        // 2. Honorarios BTE (retenciones)
        $honorariosRetencion = 0;
        $honorariosNeto      = 0;
        if (Schema::hasTable('honorarios_bte')) {
            $honorariosRetencion = (int) DB::table('honorarios_bte')
                ->where('periodo', $periodo)
                ->where('estado', '!=', 'Anulada')
                ->sum('monto_retenido');
            $honorariosNeto = (int) DB::table('honorarios_bte')
                ->where('periodo', $periodo)
                ->where('estado', '!=', 'Anulada')
                ->sum('monto_pagado');
        }

        // 3. Sueldos pagados
        $sueldosPagados = (int) DB::table('sueldos_pagados')
            ->whereBetween('fecha_pago', [$inicio->toDateString(), $fin->toDateString()])
            ->sum('monto');

        // 4. PPM estimado (1.5% del total ventas en app — aproximado)
        $ppm = (int) round($totalIngresos * 0.015);

        $totalEgresos = $facturasSii + $honorariosRetencion + $sueldosPagados + $ppm;
        $utilidad     = $totalIngresos - $totalEgresos;
        $margen       = $totalIngresos > 0 ? round(($utilidad / $totalIngresos) * 100, 1) : 0;

        // ── Breakdown egresos ─────────────────────────────────────────────────
        $breakdown = [
            ['label' => 'Facturas SII',       'monto' => $facturasSii,          'pct' => $totalIngresos > 0 ? round(($facturasSii / $totalIngresos) * 100, 1) : 0],
            ['label' => 'Honorarios (ret.)',  'monto' => $honorariosRetencion,  'pct' => $totalIngresos > 0 ? round(($honorariosRetencion / $totalIngresos) * 100, 1) : 0],
            ['label' => 'Sueldos',            'monto' => $sueldosPagados,       'pct' => $totalIngresos > 0 ? round(($sueldosPagados / $totalIngresos) * 100, 1) : 0],
            ['label' => 'PPM est.',           'monto' => $ppm,                  'pct' => $totalIngresos > 0 ? round(($ppm / $totalIngresos) * 100, 1) : 0],
        ];

        // ── Ventas SII del período ────────────────────────────────────────────
        $ventasSii = (int) DB::table('sii_resumen_mensual')
            ->where('periodo', $periodo)
            ->value('ventas_total') ?? 0;

        // ── Resumen anual ─────────────────────────────────────────────────────
        $resumenAnual = [];
        for ($m = 1; $m <= 12; $m++) {
            $ini = Carbon::create($anio, $m, 1)->startOfMonth()->toDateString();
            $fin_m = Carbon::create($anio, $m, 1)->endOfMonth()->toDateString();
            $per_m = sprintf('%04d%02d', $anio, $m);
            $per_k = $anio . '-' . sprintf('%02d', $m);

            $ing = (int) DB::table('ventas')
                ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
                ->whereYear('reservas.fecha_visita', $anio)
                ->whereMonth('reservas.fecha_visita', $m)
                ->sum(DB::raw('ventas.abono_programa + ventas.diferencia_programa'));
            $ing += (int) DB::table('ventas_directas')->whereBetween('fecha', [$ini, $fin_m])->sum('subtotal');

            $egr = (int) DB::table('egresos')->where('fuente', 'sii')->where('periodo_sii', $per_k)->sum('neto');
            if ($egr === 0) {
                $egr = (int) DB::table('egresos')->where('fuente', 'sii')->where('periodo_sii', $per_k)->sum('total');
            }
            if (Schema::hasTable('honorarios_bte')) {
                $egr += (int) DB::table('honorarios_bte')->where('periodo', $per_m)->where('estado', '!=', 'Anulada')->sum('monto_retenido');
            }
            $egr += (int) DB::table('sueldos_pagados')->whereBetween('fecha_pago', [$ini, $fin_m])->sum('monto');
            $egr += (int) round($ing * 0.015);

            if ($ing === 0 && $egr === 0 && Carbon::create($anio, $m, 1)->isFuture()) {
                $resumenAnual[$m] = null;
            } else {
                $util_m = $ing - $egr;
                $resumenAnual[$m] = [
                    'ing'     => $ing,
                    'egr'     => $egr,
                    'utilidad' => $util_m,
                    'margen'  => $ing > 0 ? round(($util_m / $ing) * 100, 1) : 0,
                ];
            }
        }

        return view('themes.backoffice.pages.reporte.financiero.utilidad', compact(
            'anio', 'mes', 'nombreMes', 'mesesNombres',
            'abonos', 'consumos', 'servicios', 'directas', 'poro',
            'totalIngresos', 'totalEgresos', 'utilidad', 'margen',
            'facturasSii', 'honorariosRetencion', 'honorariosNeto',
            'sueldosPagados', 'ppm', 'breakdown',
            'ventasSii', 'resumenAnual'
        ));
    }

    public function comparar(Request $request)
    {
        try {
            $raw = $request->input('meses', []); // puede venir como meses[] en query
            $meses = collect(is_array($raw) ? $raw : [$raw])
                ->filter()
                ->map(function($s){ return trim((string)$s); })
                ->unique()
                ->values()
                ->take(4);

            if ($meses->isEmpty()) {
                return response('Faltan meses', 422);
            }

            $rows = $meses->map(function($mmYYYY){
                $parts = explode('-', $mmYYYY);
                if (count($parts) !== 2) {
                    return null;
                }
                $mm = (int)$parts[0];
                $yy = (int)$parts[1];
                if ($mm < 1 || $mm > 12 || $yy < 2000) {
                    return null;
                }

                $desde = Carbon::create($yy, $mm, 1)->startOfMonth();
                $hasta = Carbon::create($yy, $mm, 1)->endOfMonth();

                $total = $this->totalIngresosPeriodo($desde, $hasta);

                return [
                    'label' => ucfirst(Carbon::create()->month($mm)->locale('es')->isoFormat('MMMM')).' '.$yy,
                    'mes'   => $mm,
                    'anio'  => $yy,
                    'total' => (int)$total,
                ];
            })->filter()->values();

            $rows = $rows->sortBy(function($r){
                return ($r['anio'] * 100) + $r['mes'];
            })->values();

            // Incluso con totales = 0, renderizamos la tabla
            $viewPath = 'themes.backoffice.pages.reporte.ingreso.partials._comparativa_ingresos';
            if (!view()->exists($viewPath)) {
                Log::error('Vista parcial no existe', ['view' => $viewPath]);
                return response('Parcial no encontrado: '.$viewPath, 500);
            }

            $html = view($viewPath, compact('rows'))->render();
            return response($html, 200)->header('Content-Type', 'text/html');

        } catch (\Throwable $e) {
            Log::error('FinanzasCompararError', [
                'msg' => $e->getMessage(),
                'file'=> $e->getFile(),
                'line'=> $e->getLine(),
            ]);
            return response('Error interno', 500);
        }
    }

    protected function totalIngresosPeriodo(Carbon $desde, Carbon $hasta)
    {
        // Ventas (abono + diferencia)
        $ingresosVentas = DB::table('ventas')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereBetween('reservas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(ventas.abono_programa,0) + COALESCE(ventas.diferencia_programa,0)'));

        // Consumos: detalles_consumos -> consumos -> ventas -> reservas
        $consumos = DB::table('detalles_consumos')
            ->join('consumos', 'detalles_consumos.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereBetween('reservas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(detalles_consumos.subtotal,0)'));

        // Servicios extra: detalles_servicios_extra -> consumos -> ventas -> reservas
        $serviciosExtra = DB::table('detalle_servicios_extra')
            ->join('consumos', 'detalle_servicios_extra.id_consumo', '=', 'consumos.id')
            ->join('ventas', 'consumos.id_venta', '=', 'ventas.id')
            ->join('reservas', 'ventas.id_reserva', '=', 'reservas.id')
            ->whereBetween('reservas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(detalle_servicios_extra.subtotal,0)'));

            
        $ventaDirecta = DB::table('ventas_directas')
            ->whereBetween('ventas_directas.created_at', [$desde, $hasta])
            ->sum(DB::raw('COALESCE(ventas_directas.subtotal,0)'));

        return (int) ($ingresosVentas + $consumos + $serviciosExtra + $ventaDirecta);
    }
    
}
