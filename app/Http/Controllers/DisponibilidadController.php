<?php

namespace App\Http\Controllers;

use App\Services\DisponibilidadService;

class DisponibilidadController extends Controller
{
    /**
     * GET /backoffice/disponibilidad/{fecha}
     *
     * Devuelve disponibilidad por espacio_tipo y slots de tinaja para una fecha (Y-m-d).
     * Consumido por el partial disponibilidad-resumen.blade.php via fetch().
     */
    public function resumen($fecha, DisponibilidadService $disponibilidad)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return response()->json(['error' => 'Fecha inválida'], 422);
        }

        return response()->json($disponibilidad->resumen($fecha));
    }
}
