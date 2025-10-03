<?php

namespace App\Http\Controllers;

use App\Egreso;
use App\PagoEgreso;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'egresos', 'sueldos', 'impuestos', 'ventasDirectas', 'anio'
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
            'egresos', 'sueldos', 'impuestos', 'ventasDirectas'
        ));
    }

    public function ingresosPercibidos()
    {

        $anio = now()->year;
        $mes = now()->month;

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

        // dd($impuestos);
        return view('themes.backoffice.pages.reporte.ingreso.percibido', compact(
            'anio', 'mes', 'mesNombre',
            'abonos', 'diferencias', 'consumos', 'servicios'
            , 'ventasDirectas'
        ));
    }
    
}
