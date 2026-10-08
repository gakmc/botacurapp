<?php

namespace App\Http\Controllers;

use App\Reserva;
use App\Services\DesayunoOnceService;
use Illuminate\Http\Request;

/**
 * Cambio rápido de Desayuno / Once desde la vista de reservas del día
 * (botones en la tabla). Responde JSON.
 */
class ReservaDesayunoOnceRapidoController extends Controller
{
    public function guardar(Request $request, Reserva $reserva)
    {
        $request->validate(['tipo' => 'required|in:desayuno,once']);
        if (!DesayunoOnceService::incluye($reserva)) {
            return response()->json(['ok' => false, 'mensaje' => 'El programa de esta reserva no incluye desayuno u once.'], 422);
        }
        DesayunoOnceService::fijar($reserva->id, [$request->tipo], auth()->id());
        return response()->json(['ok' => true, 'tipo' => $request->tipo, 'personas' => (int) $reserva->cantidad_personas]);
    }
}
