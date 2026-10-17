<?php

namespace App\Http\Controllers;

use App\Reserva;
use App\Services\DesayunoOnceService;
use Illuminate\Http\Request;

/**
 * Desayuno / Once desde la vista de reservas del día. Responde JSON.
 * guardar   = elegir desayuno u once.
 * confirmar = el equipo confirma en el recinto que el cliente pasó a esa comida.
 */
class ReservaDesayunoOnceRapidoController extends Controller
{
    public function guardar(Request $request, Reserva $reserva)
    {
        $request->validate(['tipo' => 'required|in:desayuno,once']);
        if (!DesayunoOnceService::incluye($reserva)) {
            return response()->json(['ok' => false, 'mensaje' => 'El programa de esta reserva no incluye desayuno u once.'], 422);
        }
        if (DesayunoOnceService::tieneConfirmacion($reserva->id)) {
            return response()->json(['ok' => false, 'mensaje' => 'Ya está confirmado. Quita la confirmación para cambiarlo.'], 422);
        }
        if (!DesayunoOnceService::fijar($reserva->id, [$request->tipo], auth()->id())) {
            return response()->json(['ok' => false, 'mensaje' => 'No se pudo guardar.'], 500);
        }
        return response()->json(['ok' => true, 'tipo' => $request->tipo, 'personas' => (int) $reserva->cantidad_personas]);
    }

    public function confirmar(Request $request, Reserva $reserva)
    {
        $request->validate(['tipo' => 'required|in:desayuno,once', 'valor' => 'required|boolean']);
        $r = DesayunoOnceService::confirmar($reserva->id, $request->tipo, (bool) $request->valor, auth()->id());
        if ($r === null) {
            return response()->json(['ok' => false, 'mensaje' => 'Primero elige desayuno u once.'], 422);
        }
        return response()->json(['ok' => true] + $r);
    }
}
