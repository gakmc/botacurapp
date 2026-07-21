<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * BotProgramasController
 *
 * GET /api/bot/programas
 * Retorna todos los programas activos con sus servicios incluidos y precio.
 * Sin auth — consumido por n8n y por el BotController internamente.
 *
 * Respuesta:
 * {
 *   "ok": true,
 *   "programas": [
 *     {
 *       "id": 3,
 *       "nombre": "Full Day",
 *       "precio": 80000,
 *       "precio_formato": "$80.000",
 *       "espacio_tipo": "estacion_full",
 *       "servicios": ["Sauna", "Tinaja", "Masaje", "Almuerzo"]
 *     },
 *     ...
 *   ]
 * }
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotProgramasController extends Controller
{
    public function index(Request $request)
    {
        // ── Cargar programas activos con sus servicios ────────────────────────
        $programas = DB::table('programas as p')
            ->leftJoin('programa_servicio as ps', 'ps.id_programa', '=', 'p.id')
            ->leftJoin('servicios as s', 's.id', '=', 'ps.id_servicio')
            ->select(
                'p.id',
                'p.nombre_programa',
                'p.valor_programa',
                'p.espacio_tipo',
                's.nombre_servicio'
            )
            ->where('p.estado', 'activo')
            ->orderBy('p.valor_programa', 'asc')
            ->orderBy('s.nombre_servicio', 'asc')
            ->get();

        // ── Agrupar servicios por programa ────────────────────────────────────
        $agrupados = [];
        foreach ($programas as $fila) {
            $id = $fila->id;
            if (!isset($agrupados[$id])) {
                $agrupados[$id] = [
                    'id'            => $id,
                    'nombre'        => $fila->nombre_programa,
                    'precio'        => (int) $fila->valor_programa,
                    'precio_formato' => '$' . number_format((int) $fila->valor_programa, 0, ',', '.'),
                    'espacio_tipo'  => $fila->espacio_tipo,
                    'servicios'     => [],
                ];
            }
            if ($fila->nombre_servicio) {
                $agrupados[$id]['servicios'][] = $fila->nombre_servicio;
            }
        }

        return response()->json([
            'ok'        => true,
            'programas' => array_values($agrupados),
        ]);
    }
}
