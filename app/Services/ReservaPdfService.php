<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ReservaPdfService
 *
 * Genera el PDF de confirmación de reserva usando barryvdh/laravel-dompdf.
 * Devuelve el contenido binario del PDF o null en caso de error.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class ReservaPdfService
{
    /**
     * Genera el PDF de confirmación para la reserva indicada.
     *
     * @param  int $reservaId
     * @return string|null  Contenido binario del PDF, o null si falla
     */
    public static function generarPdf(int $reservaId): ?string
    {
        try {
            // ── Cargar datos de la reserva ────────────────────────────
            $reserva = DB::table('reservas')->where('id', $reservaId)->first();
            if (!$reserva) {
                Log::warning("[ReservaPdf] Reserva {$reservaId} no encontrada");
                return null;
            }

            $cliente  = DB::table('clientes')->where('id', $reserva->cliente_id)->first();
            $programa = DB::table('programas')->where('id', $reserva->id_programa)->first();

            if (!$cliente || !$programa) {
                Log::warning("[ReservaPdf] Datos incompletos para reserva {$reservaId}");
                return null;
            }

            // ── Venta (puede no existir aún) ──────────────────────────
            $venta = DB::table('ventas')->where('id_reserva', $reservaId)->first();

            $totalPagar  = $venta ? (int) $venta->total_pagar : (int)$programa->valor_programa * (int)$reserva->cantidad_personas;
            $abono50     = (int) ceil($totalPagar / 2);
            $referencia  = $venta->referencia_transferencia ?? '—';

            $appUrl     = rtrim(env('APP_URL', 'http://localhost'), '/');
            $enlacePago = $appUrl . '/pago/' . $reservaId;

            // ── Servicios del programa ────────────────────────────────
            $servicios = DB::table('programa_servicio as ps')
                ->join('servicios as s', 's.id', '=', 'ps.id_servicio')
                ->where('ps.id_programa', $reserva->id_programa)
                ->pluck('s.nombre_servicio')
                ->toArray();

            // ── tipo_servicio (desayuno/once) ─────────────────────────
            $tipoServicio = DB::table('menus')
                ->where('id_reserva', $reservaId)
                ->whereNotNull('tipo_servicio')
                ->value('tipo_servicio');

            // ── Render Blade → HTML → PDF ─────────────────────────────
            $html = view('pdf.reserva_confirmacion', [
                'reserva'       => $reserva,
                'cliente'       => $cliente,
                'programa'      => $programa,
                'servicios'     => $servicios,
                'tipoServicio'  => $tipoServicio,
                'totalFormato'  => '$' . number_format($totalPagar, 0, ',', '.'),
                'abono50Formato'=> '$' . number_format($abono50, 0, ',', '.'),
                'referencia'    => $referencia,
                'enlacePago'    => $enlacePago,
            ])->render();

            $pdf = \PDF::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => false,
                    'defaultFont'          => 'DejaVu Sans',
                ]);

            $contenido = $pdf->output();
            Log::info("[ReservaPdf] PDF generado OK para reserva {$reservaId} (" . strlen($contenido) . " bytes)");
            return $contenido;

        } catch (\Exception $e) {
            Log::error("[ReservaPdf] Error generando PDF para reserva {$reservaId}: " . $e->getMessage());
            return null;
        }
    }
}
