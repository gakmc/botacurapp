<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * BotDiagController — Solo para diagnóstico en desarrollo.
 * Eliminar antes de producción.
 * GET /api/bot/diag?fecha=2026-07-23
 */
class BotDiagController extends Controller
{
    /**
     * Reset conversaciones de teléfonos de QA (56900000001 a 56900000015)
     * GET /api/bot/diag/reset-qa
     */
    public function resetQa()
    {
        // Cubre qa-bot.py (569000000xx) y qa-bot-reserva.py (56900099001)
        $affected = DB::table('bot_conversaciones')
            ->where(function ($q) {
                $q->where('usuario_id', 'like', '5690000000%')
                  ->orWhere('usuario_id', '56900099001');
            })
            ->update([
                'activo'        => 0,
                'historial_json'=> '[]',
                'motivo_cierre' => 'reset_qa',
            ]);

        return response()->json([
            'ok'       => true,
            'mensaje'  => "Reset QA: {$affected} conversaciones cerradas.",
            'affected' => $affected,
        ]);
    }

    public function slots(Request $request)
    {
        $fechas = [
            '2026-07-23' => 'Jueves 23',
            '2026-07-24' => 'Viernes 24',
            '2026-07-25' => 'Sábado 25',
            '2026-07-26' => 'Domingo 26',
        ];

        $estadosActivos = ['pendiente', 'pendiente_pago', 'pago_parcial', 'pagado', 'confirmado'];
        $capacidad = [
            'estacion_economico'  => 2,
            'estacion_intermedio' => 2,
            'estacion_full'       => 5,
            'terraza'             => 6,
            'reposera'            => 4,
        ];
        $maxSlots = 16;

        $resumen = [];

        foreach ($fechas as $fecha => $label) {
            // Slots tinaja
            $reservas = DB::table('reservas')
                ->where('fecha_visita', $fecha)
                ->whereIn('estado', $estadosActivos)
                ->get(['cantidad_personas', 'id_programa', 'estado']);

            $slotsUsados = 0;
            foreach ($reservas as $r) {
                $slotsUsados += ((int)$r->cantidad_personas >= 5) ? 2 : 1;
            }

            // Espacios por tipo
            $espacios = DB::table('reservas as r')
                ->join('programas as p', 'r.id_programa', '=', 'p.id')
                ->where('r.fecha_visita', $fecha)
                ->whereIn('r.estado', $estadosActivos)
                ->whereNotNull('p.espacio_tipo')
                ->select('p.espacio_tipo', DB::raw('COUNT(*) as usados'))
                ->groupBy('p.espacio_tipo')
                ->get()
                ->keyBy('espacio_tipo');

            $espacioDetalle = [];
            foreach ($capacidad as $tipo => $max) {
                $usados = isset($espacios[$tipo]) ? (int)$espacios[$tipo]->usados : 0;
                $espacioDetalle[$tipo] = [
                    'max'    => $max,
                    'usados' => $usados,
                    'libres' => $max - $usados,
                ];
            }

            $resumen[] = [
                'fecha'         => $fecha,
                'dia'           => $label,
                'total_reservas'=> count($reservas),
                'tinaja'        => [
                    'slots_usados' => $slotsUsados,
                    'slots_libres' => $maxSlots - $slotsUsados,
                    'slots_max'    => $maxSlots,
                ],
                'espacios'      => $espacioDetalle,
            ];
        }

        return response()->json($resumen);
    }

    public function index(Request $request)
    {
        $fecha = $request->query('fecha', date('Y-m-d'));

        // ── Programas activos ─────────────────────────────────────────────────
        $programas = DB::table('programas')
            ->where('estado', 'activo')
            ->orderBy('valor_programa')
            ->get(['id', 'nombre_programa', 'espacio_tipo', 'valor_programa', 'estado']);

        // ── Reservas del día ──────────────────────────────────────────────────
        $reservas = DB::table('reservas as r')
            ->leftJoin('programas as p', 'r.id_programa', '=', 'p.id')
            ->leftJoin('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->where('r.fecha_visita', $fecha)
            ->get([
                'r.id', 'r.id_programa', 'p.nombre_programa', 'p.espacio_tipo',
                'r.cantidad_personas', 'r.estado as estado_reserva',
                'c.nombre_cliente',
            ]);

        // ── Cálculo slots ─────────────────────────────────────────────────────
        $estadosActivos = ['pendiente', 'pendiente_pago', 'pago_parcial', 'pagado', 'confirmado'];
        $slotsUsados = 0;
        $espaciosUsados = [];
        foreach ($reservas as $r) {
            if (in_array($r->estado_reserva, $estadosActivos)) {
                $slotsUsados += ($r->cantidad_personas >= 5) ? 2 : 1;
                $tipo = $r->espacio_tipo ?? 'sin_tipo';
                $espaciosUsados[$tipo] = ($espaciosUsados[$tipo] ?? 0) + 1;
            }
        }

        // ── Test disponibilidad Full Cyber ────────────────────────────────────
        $fullCyber = DB::table('programas')
            ->where('nombre_programa', 'like', '%Cyber%')
            ->orWhere('nombre_programa', 'like', '%cyber%')
            ->orWhere('nombre_programa', 'like', '%Full%')
            ->get(['id', 'nombre_programa', 'espacio_tipo', 'estado']);

        return response()->json([
            'fecha_consultada'   => $fecha,
            'programas_activos'  => $programas,
            'full_cyber_matches' => $fullCyber,
            'reservas_del_dia'   => $reservas,
            'slots' => [
                'usados'   => $slotsUsados,
                'max'      => 16,
                'libres'   => 16 - $slotsUsados,
            ],
            'espacios_usados_por_tipo' => $espaciosUsados,
            'capacidad_config' => [
                'estacion_economico'  => 2,
                'estacion_intermedio' => 2,
                'estacion_full'       => 5,
                'terraza'             => 6,
                'reposera'            => 4,
            ],
        ]);
    }
}
