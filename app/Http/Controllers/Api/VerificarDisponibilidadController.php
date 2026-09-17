<?php

namespace App\Http\Controllers\Api;

use App\FechaDisponible;
use App\Http\Controllers\Controller;
use App\Services\DisponibilidadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificarDisponibilidadController extends Controller
{
    /**
     * GET /api/verificar-disponibilidad
     *
     * Verifica en tiempo real si una fecha sigue disponible al momento del submit del checkout.
     *
     * Parámetros:
     *   fecha         : string  YYYY-MM-DD  (requerido)
     *   wc_product_id : int                 (opcional)
     *   cantidad      : int                 (personas, default 1)
     *
     * Respuesta:
     *   { disponible: true }
     *   { disponible: false, razon: "..." }
     */
    public function verificar(Request $request, DisponibilidadService $disponibilidad)
    {
        $fecha       = $request->query('fecha', '');
        $wcProductId = $request->query('wc_product_id');
        $cantidad    = max(1, (int) $request->query('cantidad', 1));

        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return response()->json(['disponible' => false, 'razon' => 'Fecha inválida.'], 422);
        }

        // ── Criterio 1: fecha habilitada ──────────────────────────
        $habilitada = FechaDisponible::where('fecha', $fecha)->where('habilitada', true)->exists();
        if (!$habilitada) {
            return response()->json([
                'disponible' => false,
                'razon'      => 'La fecha seleccionada no está habilitada.',
            ]);
        }

        if ($wcProductId) {
            $programa = DB::table('programas')
                ->where('wc_product_id', $wcProductId)
                ->select('espacio_tipo')
                ->first();

            if ($programa && $programa->espacio_tipo) {
                $resultado = $disponibilidad->disponible($fecha, $programa->espacio_tipo, $cantidad);

                if (!$resultado['disponible']) {
                    return response()->json([
                        'disponible' => false,
                        'razon'      => $resultado['razon'] ?? 'No hay cupos disponibles para este tipo de experiencia en esa fecha.',
                    ]);
                }

                return response()->json(['disponible' => true]);
            }
        }

        $tinaja = $disponibilidad->slotsTinaja($fecha, $cantidad);
        if (!$tinaja['ok']) {
            return response()->json([
                'disponible' => false,
                'razon'      => 'No hay horarios de tinaja disponibles para esa fecha con el número de personas indicado.',
            ]);
        }

        $unidades    = $disponibilidad->unidadesLibresPorFecha($fecha);
        $totalLibres = $unidades['estacion']['libres'] + $unidades['terraza']['libres'] + $unidades['reposera']['libres'];

        if ($totalLibres <= 0) {
            return response()->json([
                'disponible' => false,
                'razon'      => 'No hay ubicaciones disponibles para esa fecha.',
            ]);
        }

        return response()->json(['disponible' => true]);
    }
}
