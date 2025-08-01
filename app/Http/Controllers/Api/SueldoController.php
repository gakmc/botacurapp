<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Sueldo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SueldoController extends Controller
{
     public function index(Request $request)
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
        $currentMonth = $request->input('mes', $fechaSeleccionada ? $fechaSeleccionada->mes : now()->month);
        $currentYear = $request->input('anio', $fechaSeleccionada ? $fechaSeleccionada->anio : now()->year);

        // Sueldos del mes seleccionado
        $sueldos = Sueldo::where('id_user', $userId)
            ->whereMonth('dia_trabajado', $currentMonth)
            ->whereYear('dia_trabajado', $currentYear)
            ->orderBy('dia_trabajado', 'asc')
            ->get();

        // Agrupar por semana
        $sueldosAgrupados = $sueldos->groupBy(function ($sueldo) {
            $fecha = Carbon::parse($sueldo->dia_trabajado);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana = $fecha->copy()->endOfWeek(Carbon::SUNDAY);

            return $inicioSemana->format('d M') . ' - ' . $finSemana->format('d M');
        });

        // Respuesta JSON
        return response()->json([
            'fechas_disponibles' => $fechasDisponibles,
            'mes' => $currentMonth,
            'anio' => $currentYear,
            'sueldos_agrupados' => $sueldosAgrupados,
        ]);
    }
}
