<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Sueldo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SueldoController extends Controller
{
    public function OLDindex(Request $request)
    {
        // Usuario autenticado por JWT
        $userId = auth('api')->id();

        // Fechas disponibles (mes/año trabajados)
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('MONTH(dia_trabajado) as mes, YEAR(dia_trabajado) as anio')
            ->groupBy('mes', 'anio')
            ->orderBy('anio', 'desc')
            ->orderBy('mes', 'desc')
            ->get();

        // Determinar mes/año actual
        $fechaSeleccionada = $fechasDisponibles->first();
        $currentMonth      = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        $currentYear       = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

        // Sueldos del mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->get();

        // Agrupar por semana
        $sueldosAgrupados = $sueldos->groupBy(function ($sueldo) {
            $fecha        = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            return $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
        });

        // Respuesta JSON
        return response()->json([
            'fechas_disponibles' => $fechasDisponibles,
            'mes'                => $currentMonth,
            'anio'               => $currentYear,
            'sueldos_agrupados'  => $sueldosAgrupados,
        ]);
    }

    public function index(Request $request)
    {
        $userId = auth('api')->id();

        // Fechas disponibles (mes/año con datos)
        $fechasDisponibles = Sueldo::where('id_user', $userId)
            ->selectRaw('YEAR(dia_trabajado) as anio, MONTH(dia_trabajado) as mes')
            ->distinct()
            ->orderBy('anio', 'desc')->orderBy('mes', 'desc')
            ->get();

        // Mes/año objetivo (con fallback)
        $primera = $fechasDisponibles->first();
        $mes     = (int) $request->input('mes', $primera->mes ?? now()->month);
        $anio    = (int) $request->input('anio', $primera->anio ?? now()->year);
        $mes     = max(1, min(12, $mes));

        // Rango del mes
        $inicioMes = Carbon::create($anio, $mes, 1)->startOfDay();
        $finMes    = (clone $inicioMes)->endOfMonth();

        // Sueldos del mes (selecciona columnas necesarias)
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereBetween('dia_trabajado', [$inicioMes, $finMes])
            ->orderBy('dia_trabajado', 'asc')
            ->get(['id', 'dia_trabajado', 'valor_dia', 'sub_sueldo', 'total_a_pagar', 'id_propina']);

        // Agrupar a semanas (lunes-domingo)
        Carbon::setWeekStartsAt(Carbon::MONDAY);
        Carbon::setWeekEndsAt(Carbon::SUNDAY);

        $semanas = [];
        $tmp     = $sueldos->groupBy(function ($row) {
            $f = Carbon::parse($row->dia_trabajado);
            return $f->isoWeekYear() . 'W' . $f->isoWeek(); // clave estable
        });

        foreach ($tmp as $key => $items) {
            $ini = Carbon::parse($items->first()->dia_trabajado)->startOfWeek();
            $fin = Carbon::parse($items->first()->dia_trabajado)->endOfWeek();

            $semanas[] = [
                'codigo_semana' => $key,                 // p.ej. "2025W33"
                'inicio'        => $ini->toDateString(), // "YYYY-MM-DD"
                'fin'           => $fin->toDateString(),
                'totales'       => [
                    'dias'          => $items->count(),
                    'valor_dia'     => (int) $items->sum('valor_dia'),
                    'sub_sueldo'    => (int) $items->sum('sub_sueldo'),
                    'total_a_pagar' => (int) $items->sum('total_a_pagar'),
                ],
                'items'         => $items->map(function ($r) {
                    return [
                        'id'            => $r->id,
                        'dia_trabajado' => Carbon::parse($r->dia_trabajado)->toDateString(),
                        'valor_dia'     => (int) $r->valor_dia,
                        'sub_sueldo'    => (int) $r->sub_sueldo,
                        'total_a_pagar' => (int) $r->total_a_pagar,
                        'id_propina'    => $r->id_propina,
                    ];
                })->values(),
            ];
        }

        // Resumen del mes
        $resumenMes = [
            'total_dias'       => $sueldos->count(),
            'total_valor_dia'  => (int) $sueldos->sum('valor_dia'),
            'total_sub_sueldo' => (int) $sueldos->sum('sub_sueldo'),
            'total_a_pagar'    => (int) $sueldos->sum('total_a_pagar'),
            'rango'            => ['inicio' => $inicioMes->toDateString(), 'fin' => $finMes->toDateString()],
        ];


        return response()->json([
            'filtros'            => ['mes' => $mes, 'anio' => $anio],
            'fechas_disponibles' => $fechasDisponibles, // [{anio,mes}]
            'resumen_mes'        => $resumenMes,
            'semanas'            => $semanas, // lista estable y fácil de mapear en Flutter
        ]);
    }

}
