<?php

namespace App\Http\Controllers\Api;

use App\FechaDisponible;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificarDisponibilidadController extends Controller
{
    /**
     * GET /api/verificar-disponibilidad
     *
     * Verifica en tiempo real si una fecha sigue disponible al momento del submit del checkout.
     * Aplica los mismos 4 criterios que /api/fechas-disponibles pero para una fecha específica.
     *
     * Parámetros:
     *   fecha         : string  YYYY-MM-DD  (requerido)
     *   wc_product_id : int                 (opcional, activa criterio 4)
     *   cantidad      : int                 (personas, default 1, activa criterio 3 preciso)
     *
     * Respuesta:
     *   { disponible: true }
     *   { disponible: false, razon: "..." }
     */

    
    public function verificar(Request $request)
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

        // ── Criterio 2: ubicaciones disponibles ───────────────────
        $totalUbicaciones    = DB::table('ubicaciones')->count();
        $ubicacionesOcupadas = DB::table('visitas')
            ->join('reservas', 'visitas.id_reserva', '=', 'reservas.id')
            ->whereNotNull('visitas.id_ubicacion')
            ->whereRaw('DATE(reservas.fecha_visita) = ?', [$fecha])
            ->distinct()
            ->count('visitas.id_ubicacion');

        if ($ubicacionesOcupadas >= $totalUbicaciones) {
            return response()->json([
                'disponible' => false,
                'razon'      => 'No hay ubicaciones disponibles para esa fecha.',
            ]);
        }

        // ── Criterio 3: slots de Spa ───────────────────────────
        $slotsReservas = (int) DB::table('reservas')
            ->whereRaw('DATE(fecha_visita) = ?', [$fecha])
            ->selectRaw('COALESCE(SUM(CEIL(cantidad_personas / 5)), 0) as slots')
            ->value('slots');

        $slotsOrders = (int) DB::table('woocommerce_orders')
            ->whereNull('reserva_id')
            ->where('procesado', 'pendiente')
            ->whereRaw('DATE(fecha_visita_wc) = ?', [$fecha])
            ->whereNotNull('cantidad_personas')
            ->selectRaw('COALESCE(SUM(CEIL(cantidad_personas / 5)), 0) as slots')
            ->value('slots');

        $slotsNuevos = (int) ceil($cantidad / 5);

        if (($slotsReservas + $slotsOrders + $slotsNuevos) > 16) {
            return response()->json([
                'disponible' => false,
                'razon'      => 'No hay horarios de tinaja disponibles para esa fecha con el número de personas indicado.',
            ]);
        }

        if ($wcProductId) {
            $programa = DB::table('programas')
                ->where('wc_product_id', $wcProductId)
                ->select('espacio_tipo')
                ->first();

            if ($programa && $programa->espacio_tipo) {
                $espacioTipo = $programa->espacio_tipo;
                $maxCupo     = config('woocommerce.wc_espacios.' . $espacioTipo, 0);

                if ($maxCupo > 0) {
                    // divisor > 0: terraza (6) y reposera (2) → ceil(personas/divisor) ubicaciones
                    // divisor = 0: estaciones → 1 ubicación por reserva
                    $divisor    = (int) config('woocommerce.wc_personas_por_ubicacion.' . $espacioTipo, 0);
                    $nuevosCupos = $divisor > 0 ? (int) ceil($cantidad / $divisor) : 1;

                    if ($divisor > 0) {
                        $cuposReservas = (int) DB::table('reservas')
                            ->join('programas', 'reservas.id_programa', '=', 'programas.id')
                            ->where('programas.espacio_tipo', $espacioTipo)
                            ->whereRaw('DATE(reservas.fecha_visita) = ?', [$fecha])
                            ->selectRaw("COALESCE(SUM(CEIL(reservas.cantidad_personas / {$divisor})), 0) as cupos")
                            ->value('cupos');

                        $cuposOrders = (int) DB::table('woocommerce_orders')
                            ->join('programas', 'woocommerce_orders.wc_product_id', '=', 'programas.wc_product_id')
                            ->where('programas.espacio_tipo', $espacioTipo)
                            ->whereNull('woocommerce_orders.reserva_id')
                            ->where('woocommerce_orders.procesado', 'pendiente')
                            ->whereRaw('DATE(woocommerce_orders.fecha_visita_wc) = ?', [$fecha])
                            ->whereNotNull('woocommerce_orders.cantidad_personas')
                            ->selectRaw("COALESCE(SUM(CEIL(woocommerce_orders.cantidad_personas / {$divisor})), 0) as cupos")
                            ->value('cupos');
                    } else {
                        $cuposReservas = (int) DB::table('reservas')
                            ->join('programas', 'reservas.id_programa', '=', 'programas.id')
                            ->where('programas.espacio_tipo', $espacioTipo)
                            ->whereRaw('DATE(reservas.fecha_visita) = ?', [$fecha])
                            ->count();

                        $cuposOrders = (int) DB::table('woocommerce_orders')
                            ->join('programas', 'woocommerce_orders.wc_product_id', '=', 'programas.wc_product_id')
                            ->where('programas.espacio_tipo', $espacioTipo)
                            ->whereNull('woocommerce_orders.reserva_id')
                            ->where('woocommerce_orders.procesado', 'pendiente')
                            ->whereRaw('DATE(woocommerce_orders.fecha_visita_wc) = ?', [$fecha])
                            ->count();
                    }

                    if (($cuposReservas + $cuposOrders + $nuevosCupos) > $maxCupo) {
                        return response()->json([
                            'disponible' => false,
                            'razon'      => 'No hay cupos disponibles para este tipo de experiencia en esa fecha.',
                        ]);
                    }
                }
            }
        }

        return response()->json(['disponible' => true]);
    }
}
