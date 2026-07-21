<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DisponibilidadController extends Controller
{
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

        // ── Slots de tinaja ───────────────────────────────────────
        $maxSlots = 16;

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
            'tinaja'  => [
                'max_slots'   => $maxSlots,
                'usados'      => $slotsUsados,
                'disponibles' => max(0, $maxSlots - $slotsUsados),
            ],
        ]);
    }
}
