<?php

namespace App\Http\Controllers;

use App\SueldoPagado;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Confirma que la transferencia bancaria de un sueldo ya se hizo
 * (después de subir el CSV al banco y verificar). No toca bono/motivo,
 * que se guardan por separado con SueldoController::guardarBonos()
 * en cualquier momento antes de exportar el CSV.
 */
class SueldoPagadoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'sueldos_seleccionados' => 'required|array|min:1',
        ]);

        foreach ($request->sueldos_seleccionados as $item) {
            $data = json_decode($item, true);

            SueldoPagado::updateOrCreate(
                [
                    'user_id'       => $data['user_id'],
                    'semana_inicio' => $data['inicio'],
                    'semana_fin'    => $data['fin'],
                ],
                [
                    'monto'         => $data['total'],
                    'fecha_pago'    => Carbon::now()->format('Y-m-d'),
                    'confirmado'    => true,
                    'confirmado_at' => Carbon::now(),
                ]
            );
        }

        return back()->with('success', 'Transferencia confirmada para los seleccionados.');
    }
}
