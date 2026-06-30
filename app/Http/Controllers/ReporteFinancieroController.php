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
    // ──────────────────────────────────────────────────────────────────────────
    // EGRESOS: acumulado por categoría/subcategoría
    // ──────────────────────────────────────────────────────────────────────────

    public function acumuladoEgresos(Request $request, $anio = null, $mes = null)
    {
        $anio = (int) ($anio ?? $request->input('anio', now()->year));
        $mes  = (int) ($mes  ?? $request->input('mes',  now()->month));

        $inicio   = Carbon::create($anio, $mes, 1)->startOfMonth()->toDateString();
        $fin      = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();
        $mesNombre = Carbon::create($anio, $mes, 1)->locale('es')->isoFormat('MMMM');

        // ── Egresos del período con categoría/subcategoría/proveedor ──────────
        $filas = DB::table('egresos')
            ->leftJoin('categorias_compras',    'egresos.categoria_id',    '=', 'categorias_compras.id')
            ->leftJoin('subcategorias_compras', 'egresos.subcategoria_id', '=', 'subcategorias_compras.id')
            ->leftJoin('proveedores',           'egresos.proveedor_id',    '=', 'proveedores.id')
            ->select(
                'egresos.id',
                'egresos.fecha_egreso',
                'egresos.descripcion',
                'egresos.numero_documento',
                'egresos.neto',
                'egresos.iva',
                'egresos.total',
                'egresos.fuente',
                'egresos.estado',
                DB::raw('COALESCE(categorias_compras.nombre, "Sin categoría") as categoria_nombre'),
                DB::raw('COALESCE(subcategorias_compras.nombre, "Sin subcategoría") as subcategoria_nombre'),
                DB::raw('proveedores.nombre as proveedor_nombre')
            )
            ->whereBetween('egresos.fecha_egreso', [$inicio, $fin])
            ->where('egresos.estado', '!=', 'anulado')
            ->orderBy('categorias_compras.nombre')
            ->orderBy('subcategorias_compras.nombre')
            ->orderBy('egresos.fecha_egreso')
            ->get();

        // ── Agrupar por categoría → subcategoría ─────────────────────────────
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

            $agrupado[$cat]['total']                        += $tot;
            $agrupado[$cat]['subcategorias'][$sub]['total'] += $tot;
            $agrupado[$cat]['subcategorias'][$sub]['filas'][] = $fila;
        }

        // Ordenar categorías por total desc
        uasort($agrupado, function($a, $b) { return $b['total'] - $a['total']; });

        // ── Total general de egresos ──────────────────────────────────────────
        $totalGeneral = array_sum(array_column($agrupado, 'total'));

        // ── Honorarios BTE del mes ────────────────────────────────────────────
        $periodo  = sprintf('%04d%02d', $anio, $mes);
        $totalBte = (int) HonorarioBte::where('periodo', $periodo)
            ->where('estado', '!=', 'Anulada')
            ->sum('monto_pagado');

        // ── Sueldos del mes ───────────────────────────────────────────────────
        $totalSueldos = (int) DB::table('sueldos_pagados')
            ->whereBetween('fecha_pago', [$inicio, $fin])
            ->sum('monto');

        // ── Mes anterior (para comparación) ──────────────────────────────────
        $antInicio = Carbon::create($anio, $mes, 1)->subMonth()->startOfMonth()->toDateString();
        $antFin    = Carbon::create($anio, $mes, 1)->subMonth()->endOfMonth()->toDateString();

        $totalAnterior = (int) DB::table('egresos')
            ->whereBetween('fecha_egreso', [$antInicio, $antFin])
            ->where('estado', '!=', 'anulado')
            ->sum(DB::raw('COALESCE(total, neto, 0)'));

        // Totales por categoría del mes anterior (para mostrar variación)
        $anterioresRaw = DB::table('egresos')
            ->leftJoin('categorias_compras', 'egresos.categoria_id', '=', 'categorias_compras.id')
            ->selectRaw('COALESCE(categorias_compras.nombre, "Sin categoría") as cat, SUM(COALESCE(egresos.total, egresos.neto, 0)) as total')
            ->whereBetween('egresos.fecha_egreso', [$antInicio, $antFin])
            ->where('egresos.estado', '!=', 'anulado')
            ->groupBy('cat')
            ->get();

        $anteriores = [];
        foreach ($anterioresRaw as $r) {
            $anteriores[$r->cat] = (int) $r->total;
        }

        // ── Resumen anual (total por mes) ─────────────────────────────────────
        $anualRaw = DB::table('egresos')
            ->selectRaw('MONTH(fecha_egreso) as mn, SUM(COALESCE(total, neto, 0)) as total')
            ->whereYear('fecha_egreso', $anio)
            ->where('estado', '!=', 'anulado')
            ->groupBy('mn')
            ->pluck('total', 'mn');

        $resumenAnual = [];
        for ($m = 1; $m <= 12; $m++) {
            $resumenAnual[$m] = (int) ($anualRaw[$m] ?? 0);
        }

        // ── Datos para gráfico de dona ────────────────────────────────────────
        $chartLabels = [];
        $chartData   = [];
        foreach ($agrupado as $cat => $datos) {
            if ($datos['total'] > 0) {
                $chartLabels[] = $cat;
                $chartData[]   = $datos['total'];
            }
        }

        return view('themes.backoffice.pages.reporte.egresos.acumulado', compact(
            'anio', 'mes', 'mesNombre',
            'agrupado', 'totalGeneral', 'totalBte', 'totalSueldos',
            'totalAnterior', 'anteriores',
            'resumenAnual', 'chartLabels', 'chartData'
        ));
    }

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
                ->whereNotNull('pa