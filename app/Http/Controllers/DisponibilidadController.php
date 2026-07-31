<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DisponibilidadController extends Controller
{
    /**
     * Agrupa espacio_tipo específicos bajo una categoría general.
     * El trabajador del centro puede reservar estacion_full aunque no
     * queden cupos de ese subtipo puntual, si sobra cupo en otro subtipo
     * de estación (economico/intermedio) — por eso la disponibilidad
     * relevante para el backoffice es la suma de los 3 subtipos.
     */
    private $grupos = [
        'estacion' => ['estacion_economico', 'estacion_intermedio', 'estacion_full'],
    ];

    /**
     * Etiquetas legibles para el resumen mostrado en el backoffice.
     * Un espacio_tipo sin entrada aquí igual aparece (fallback a ucfirst),
     * para que el resumen sea dinámico si se agregan espacios nuevos.
     */
    private $labels = [
        'estacion' => 'Estaciones',
        'terraza'  => 'Terrazas',
        'reposera' => 'Reposeras',
    ];

    /**
     * GET /backoffice/disponibilidad/{fecha}
     *
     * Devuelve disponibilidad por espacio_tipo y slots de tinaja para una fecha (Y-m-d).
     * Consumido por el partial disponibilidad-resumen.blade.php via fetch().
     */

    public function resumen($fecha)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return response()->json(['error' => 'Fecha inválida'], 422);
        }

        // ── Disponibilidad por espacio_tipo ───────────────────────
        $espacioConfig = config('woocommerce.wc_espacios', []);
        $divisorConfig = config('woocommerce.wc_personas_por_ubicacion', []);

        $espacios = [];

        foreach ($espacioConfig as $tipo => $max) {
            $divisor = (int) ($divisorConfig[$tipo] ?? 0);

            if ($divisor > 0) {
                // Terraza / reposera: ceil(personas/divisor) ubicaciones por reserva
                $usadosReservas = (int) DB::table('reservas')
                    ->join('programas', 'reservas.id_programa', '=', 'programas.id')
                    ->where('programas.espacio_tipo', $tipo)
                    ->whereRaw('DATE(reservas.fecha_visita) = ?', [$fecha])
                    ->selectRaw("COALESCE(SUM(CEIL(reservas.cantidad_personas / {$divisor})), 0) as usados")
                    ->value('usados');

                $usadosOrders = (int) DB::table('woocommerce_orders')
                    ->join('programas', 'woocommerce_orders.wc_product_id', '=', 'programas.wc_product_id')
                    ->where('programas.espacio_tipo', $tipo)
                    ->whereNull('woocommerce_orders.reserva_id')
                    ->where('woocommerce_orders.procesado', 'pendiente')
                    ->whereRaw('DATE(woocommerce_orders.fecha_visita_wc) = ?', [$fecha])
                    ->whereNotNull('woocommerce_orders.cantidad_personas')
                    ->selectRaw("COALESCE(SUM(CEIL(woocommerce_orders.cantidad_personas / {$divisor})), 0) as usados")
                    ->value('usados');
            } else {
                // Estaciones: 1 reserva = 1 ubicación
                $usadosReservas = (int) DB::table('reservas')
                    ->join('programas', 'reservas.id_programa', '=', 'programas.id')
                    ->where('programas.espacio_tipo', $tipo)
                    ->whereRaw('DATE(reservas.fecha_visita) = ?', [$fecha])
                    ->count();

                $usadosOrders = (int) DB::table('woocommerce_orders')
                    ->join('programas', 'woocommerce_orders.wc_product_id', '=', 'programas.wc_product_id')
                    ->where('programas.espacio_tipo', $tipo)
                    ->whereNull('woocommerce_orders.reserva_id')
                    ->where('woocommerce_orders.procesado', 'pendiente')
                    ->whereRaw('DATE(woocommerce_orders.fecha_visita_wc) = ?', [$fecha])
                    ->count();
            }

            $usados = $usadosReservas + $usadosOrders;

            $espacios[$tipo] = [
                'max'         => $max,
                'usados'      => $usados,
                'disponibles' => max(0, $max - $usados),
            ];
        }

        // ── Disponibilidad agrupada (ej. estacion = suma de sus subtipos) ──
        foreach ($this->grupos as $categoria => $tipos) {
            $max    = 0;
            $usados = 0;

            foreach ($tipos as $tipo) {
                if (!isset($espacios[$tipo])) {
                    continue;
                }

                $max    += $espacios[$tipo]['max'];
                $usados += $espacios[$tipo]['usados'];
            }

            $espacios[$categoria] = [
                'max'         => $max,
                'usados'      => $usados,
                'disponibles' => max(0, $max - $usados),
            ];
        }

        // ── Resumen listo para mostrar (agrupado, sin subtipos internos) ──
        // Los tipos que pertenecen a un grupo (ej. estacion_full) no se listan
        // sueltos aquí; se muestran solo bajo su categoría (ej. estacion).
        $tiposAgrupados = array_merge(...array_values($this->grupos));
        $resumen = [];

        foreach ($this->grupos as $categoria => $tipos) {
            if (!isset($espacios[$categoria])) {
                continue;
            }

            $resumen[$categoria] = array_merge($espacios[$categoria], [
                'label' => $this->labels[$categoria] ?? ucfirst(str_replace('_', ' ', $categoria)),
            ]);
        }

        foreach ($espacioConfig as $tipo => $max) {
            if (in_array($tipo, $tiposAgrupados, true)) {
                continue;
            }

            $resumen[$tipo] = array_merge($espacios[$tipo], [
                'label' => $this->labels[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo)),
            ]);
        }

        // ── Slots de tinaja ───────────────────────────────────────
        $maxSlots = config('app.cantidad_slot_spa');

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

        $slotsUsados = $slotsReservas + $slotsOrders;

        return response()->json([
            'fecha'   => $fecha,
            'espacios' => $espacios,
            'resumen' => $resumen,
            'tinaja'  => [
                'max_slots'   => $maxSlots,
                'usados'      => $slotsUsados,
                'disponibles' => max(0, $maxSlots - $slotsUsados),
            ],
        ]);
    }
}
