<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
 *   "espacio": { "tipo": "terraza", "usados": 2, "max": 10, "libres": 8 }
 * }
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class DisponibilidadController extends Controller
{
    /**
     * Capacidad máxima por espacio_tipo.
     * terraza y reposera se suman (pool compartido para Wellness Day/Plus).
     * Las estaciones son independientes por nivel.
     */
    private $capacidad = [
        'estacion_economico'  => 2,
        'estacion_intermedio' => 2,
        'estacion_full'       => 5,
        'terraza'             => 6,
        'reposera'            => 4,
    ];

    /**
     * Tipos que comparten pool de disponibilidad (terraza + reposera + wellness).
     * Wellness Day / Plus usan espacio_tipo='wellness' pero comparten el mismo pool físico.
     */
    private $poolFlexible = ['terraza', 'reposera', 'wellness'];

    /** Máximo de slots de tinaja por día (8 T1 + 8 T2) */
    private $maxSlotsTinaja = 16;

    // -------------------------------------------------------------------------

    public function check(Request $request)
    {
        // ── Validar parámetros ────────────────────────────────────────────────
        $request->validate([
            'fecha'          => 'required|date|after_or_equal:today',
            'programa_id'    => 'nullable|integer|exists:programas,id',
            'wc_product_id'  => 'nullable|integer',
            'personas'       => 'nullable|integer|min:1|max:20',
        ]);

        $fecha   = $request->fecha;
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

        // Según el flujograma: si no tiene espacio_tipo, solo verificar tinaja
        if (empty($programa->espacio_tipo)) {
            $slotsUsados = $this->contarSlotsUsados($fecha);
            $slotsNuevos = $personas >= 5 ? 2 : 1;
            $tinajaOk    = ($slotsUsados + $slotsNuevos) <= $this->maxSlotsTinaja;
            return response()->json([
                'ok'          => true,
                'disponible'  => $tinajaOk,
                'fecha'       => $fecha,
                'programa'    => $programa->nombre_programa,
                'espacio_tipo'=> null,
                'personas'    => $personas,
                'tinaja'      => [
                    'slots_usados' => $slotsUsados,
                    'slots_nuevos' => $slotsNuevos,
                    'slots_max'    => $this->maxSlotsTinaja,
                    'slots_libres' => max(0, $this->maxSlotsTinaja - $slotsUsados),
                    'ok'           => $tinajaOk,
                ],
                'espacio'     => ['tipo' => null, 'nota' => 'Sin espacio_tipo configurado — solo se verifica tinaja'],
                'motivo_no_disponible' => !$tinajaOk ? 'Los horarios de tinaja están completos para ese día.' : null,
            ]);
        }

        // ── 1. Verificar slots de tinaja ──────────────────────────────────────
        $slotsUsados = $this->contarSlotsUsados($fecha);
        $slotsNuevos  = $personas >= 5 ? 2 : 1;
        $slotsLibres  = $this->maxSlotsTinaja - $slotsUsados;
        $tinajaOk     = ($slotsUsados + $slotsNuevos) <= $this->maxSlotsTinaja;

        // Early return si tinaja está llena (flujograma: NO → disponible:false sin revisar espacio)
        if (!$tinajaOk) {
            return response()->json([
                'ok'         => true,
                'disponible' => false,
                'fecha'      => $fecha,
                'programa'   => $programa->nombre_programa,
                'personas'   => $personas,
                'tinaja'     => [
                    'slots_usados' => $slotsUsados,
                    'slots_nuevos' => $slotsNuevos,
                    'slots_max'    => $this->maxSlotsTinaja,
                    'slots_libres' => 0,
                    'ok'           => false,
                ],
                'motivo_no_disponible' => 'Los horarios de tinaja están completos para ese día.',
            ]);
        }

        // ── 2. Verificar disponibilidad de espacio ────────────────────────────
        $espacioTipo = $programa->espacio_tipo;
        $esFlexible  = in_array($espacioTipo, $this->poolFlexible);

        if ($esFlexible) {
            // Wellness Day / Plus: terraza O reposera → pool combinado
            $usadosPool = $this->contarEspaciosUsados($fecha, $this->poolFlexible);
            $maxPool    = $this->capacidad['terraza'] + $this->capacidad['reposera']; // 6+4=10
            $libresPool = $maxPool - $usadosPool;
            $espacioOk  = $libresPool > 0;

            $espacioInfo = [
                'tipo'      => 'terraza + reposera (flexible)',
                'usados'    => $usadosPool,
                'max'       => $maxPool,
                'libres'    => max(0, $libresPool),
            ];
        } else {
            // Full Day / estaciones: tipo fijo
            $usados    = $this->contarEspaciosUsados($fecha, [$espacioTipo]);
            $max       = $this->capacidad[$espacioTipo] ?? 0;
            $libres    = $max - $usados;
            $espacioOk = $libres > 0;

            $espacioInfo = [
                'tipo'   => $espacioTipo,
                'usados' => $usados,
                'max'    => $max,
                'libres' => max(0, $libres),
            ];
        }

        // ── 3. Disponible si AMBOS recursos tienen cupo ───────────────────────
        $disponible = $tinajaOk && $espacioOk;

        return response()->json([
            'ok'          => true,
            'disponible'  => $disponible,
            'fecha'       => $fecha,
            'programa'    => $programa->nombre_programa,
            'espacio_tipo'=> $espacioTipo,
            'personas'    => $personas,
            'tinaja'      => [
                'slots_usados' => $slotsUsados,
                'slots_nuevos' => $slotsNuevos,
                'slots_max'    => $this->maxSlotsTinaja,
                'slots_libres' => max(0, $slotsLibres),
                'ok'           => $tinajaOk,
            ],
            'espacio'     => array_merge($espacioInfo, ['ok' => $espacioOk]),
            'motivo_no_disponible' => !$disponible ? $this->motivoNoDisponible($tinajaOk, $espacioOk) : null,
        ]);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Estados de reserva que efectivamente ocupan slot de tinaja.
     * Canceladas/abandonadas NO consumen cupo.
     */
    // 'pendiente' = estado legacy de producción; 'pendiente_pago' = bot WhatsApp
    private $estadosOcupados = ['pendiente', 'pendiente_pago', 'pago_parcial', 'pagado', 'confirmado'];

    /**
     * Suma los slots de tinaja consumidos por las reservas activas del día.
     * Grupos >= 5 personas → 2 slots, resto → 1 slot.
     */
    private function contarSlotsUsados(string $fecha): int
    {
        $reservas = DB::table('reservas')
            ->where('fecha_visita', $fecha)
            ->whereIn('estado', $this->estadosOcupados)
            ->select('cantidad_personas')
            ->get();

        $slots = 0;
        foreach ($reservas as $r) {
            $slots += ((int) $r->cantidad_personas >= 5) ? 2 : 1;
        }

        return $slots;
    }

    /**
     * Cuenta cuántas reservas activas del día usan un espacio de los tipos indicados.
     */
    private function contarEspaciosUsados(string $fecha, array $tipos): int
    {
        return (int) DB::table('reservas as r')
            ->join('programas as p', 'r.id_programa', '=', 'p.id')
            ->where('r.fecha_visita', $fecha)
            ->whereIn('r.estado', $this->estadosOcupados)
            ->whereIn('p.espacio_tipo', $tipos)
            ->count();
    }

    private function motivoNoDisponible(bool $tinajaOk, bool $espacioOk): string
    {
        if (!$tinajaOk && !$espacioOk) {
            return 'Sin cupo de tinaja ni de espacio para ese día.';
        }
        if (!$tinajaOk) {
            return 'Los horarios de tinaja están completos para ese día.';
        }
        return 'No hay espacios disponibles para ese programa en ese día.';
    }
}
