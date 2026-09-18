<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DisponibilidadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * DisponibilidadController
 *
 * Verifica si hay cupo para un programa en una fecha dada.
 * El cliente solo ve "disponible / no disponible" — sin horario ni ubicación.
 * El humano asigna esos detalles después de confirmar la reserva.
 *
 * GET /api/disponibilidad
 *   ?fecha=2026-07-15
 *   &programa_id=2          (id de la tabla programas)
 *   &personas=3             (opcional, default 1)
 *
 * También acepta:
 *   &wc_product_id=456      (en vez de programa_id, para llamadas desde WooCommerce)
 *
 * Respuesta:
 * {
 *   "disponible": true,
 *   "fecha": "2026-07-15",
 *   "programa": "Wellness Day",
 *   "espacio_tipo": "terraza",
 *   "personas": 3,
 *   "tinaja": { "slots_usados": 8, "slots_max": 16, "slots_libres": 8 },
 *   "espacio": { "tipo": "wellness", "usados": 2, "max": 44, "libres": 42 }
 * }
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class DisponibilidadController extends Controller
{
    public function check(Request $request)
    {
        $disponibilidad = app(DisponibilidadService::class);

        // ── Validar parámetros ────────────────────────────────────────────────
        $request->validate([
            'fecha'          => 'required|date|after_or_equal:today',
            'programa_id'    => 'nullable|integer|exists:programas,id',
            'wc_product_id'  => 'nullable|integer',
            'personas'       => 'nullable|integer|min:1|max:20',
        ]);

        $fecha    = $request->fecha;
        $personas = (int) ($request->personas ?? 1);

        // ── Resolver programa ─────────────────────────────────────────────────
        $programa = null;

        if ($request->filled('programa_id')) {
            $programa = DB::table('programas')->where('id', $request->programa_id)->first();
        } elseif ($request->filled('wc_product_id')) {
            $programa = DB::table('programas')->where('wc_product_id', $request->wc_product_id)->first();
        }

        if (!$programa) {
            return response()->json([
                'ok'    => false,
                'error' => 'Programa no encontrado.',
            ], 404);
        }

        if (empty($programa->espacio_tipo)) {
            return response()->json([
                'ok'    => false,
                'error' => 'El programa no tiene espacio_tipo configurado.',
            ], 422);
        }

        $resultado = $disponibilidad->disponible($fecha, $programa->espacio_tipo, $personas);

        return response()->json([
            'ok'           => true,
            'disponible'   => $resultado['disponible'],
            'fecha'        => $fecha,
            'programa'     => $programa->nombre_programa,
            'espacio_tipo' => $programa->espacio_tipo,
            'personas'     => $personas,
            'tinaja'       => [
                'slots_usados' => $resultado['tinaja']['usados'],
                'slots_nuevos' => $resultado['tinaja']['slots_nuevos'],
                'slots_max'    => $resultado['tinaja']['max_slots'],
                'slots_libres' => $resultado['tinaja']['disponibles'],
                'ok'           => $resultado['tinaja']['ok'],
            ],
            'espacio' => $resultado['espacio'] ?? null,
            'motivo_no_disponible' => $resultado['razon'],
        ]);
    }
}
