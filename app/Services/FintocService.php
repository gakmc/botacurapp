<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FintocService — Verificación de transferencias bancarias vía Fintoc
 *
 * Flujo:
 *   1. Al crear la reserva, se genera referencia_transferencia = "BTC-{id}-{YYMMDD}"
 *   2. El cliente la ingresa en el campo "comentario" / "motivo" de su transferencia
 *   3. Fintoc detecta el movimiento en BancoEstado y llama al webhook POST /api/fintoc/webhook
 *   4. handleWebhook() busca la venta por referencia y actualiza el estado de pago
 *
 * Variables .env requeridas (cuando tengas cuenta Fintoc):
 *   FINTOC_SECRET_KEY=sk_live_xxxx       # tu clave secreta de producción
 *   FINTOC_WEBHOOK_SECRET=whsec_xxxx     # para validar firma HMAC del webhook
 *   FINTOC_LINK_TOKEN=link_xxxx          # link token de tu cuenta BancoEstado
 *   FINTOC_ACCOUNT_ID=acc_xxxx          # id de la cuenta BancoEstado en Fintoc
 *
 * Documentación: https://fintoc.com/docs
 */
class FintocService
{
    const REFERENCE_PREFIX = 'BTC';

    /**
     * Genera el código de referencia único para una venta.
     * Formato: BTC-{ventaId}-{YYMMDD}
     *
     * El cliente debe ingresar este código en el comentario/mensaje de la transferencia.
     */
    public static function generateReference(int $ventaId): string
    {
        $date = now()->format('ymd'); // e.g. 260720
        return self::REFERENCE_PREFIX . '-' . $ventaId . '-' . $date;
    }

    /**
     * Valida la firma HMAC del webhook de Fintoc.
     * Fintoc envía: X-Fintoc-Signature: sha256={hex}
     *
     * @param  string $rawBody    Cuerpo raw de la request
     * @param  string $signature  Header X-Fintoc-Signature
     * @return bool
     */
    public static function validateWebhookSignature(string $rawBody, string $signature): bool
    {
        $secret = env('FINTOC_WEBHOOK_SECRET', '');
        if (empty($secret)) {
            // Si no hay secret configurado, skip validación (solo en desarrollo)
            Log::warning('[Fintoc] FINTOC_WEBHOOK_SECRET no configurado — saltando validación de firma');
            return true;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Procesa un evento webhook de Fintoc.
     *
     * Tipos de evento relevantes:
     *   subscription.payment_intent.succeeded  (pago recibido)
     *   account.refreshed                       (actualización de cuenta)
     *
     * Estructura típica del evento (simplificada):
     * {
     *   "type": "subscription.payment_intent.succeeded",
     *   "data": {
     *     "object": {
     *       "amount": 50000,
     *       "currency": "clp",
     *       "description": "BTC-42-260720",
     *       "reference_id": "BTC-42-260720",
     *       "sender_account": { "name": "Juan Pérez", "number": "..." }
     *     }
     *   }
     * }
     *
     * @param  array $payload  Payload decodificado del webhook
     * @return array ['ok'=>bool, 'mensaje'=>'...']
     */
    public static function handleWebhook(array $payload): array
    {
        $type = $payload['type'] ?? '';
        Log::info('[Fintoc] Webhook recibido', ['type' => $type]);

        if ($type !== 'subscription.payment_intent.succeeded') {
            return ['ok' => true, 'mensaje' => 'Evento ignorado: ' . $type];
        }

        $obj    = $payload['data']['object'] ?? [];
        $monto  = (int) ($obj['amount'] ?? 0);
        $desc   = $obj['description'] ?? $obj['reference_id'] ?? '';
        $sender = $obj['sender_account']['name'] ?? 'Desconocido';

        // Buscar referencia en la descripción (BTC-{id}-{date})
        $referencia = self::extractReference($desc);
        if (!$referencia) {
            Log::warning('[Fintoc] No se encontró referencia BTC en la transferencia', [
                'description' => $desc,
                'monto'       => $monto,
                'sender'      => $sender,
            ]);
            return ['ok' => false, 'mensaje' => 'Referencia no encontrada en descripción: ' . $desc];
        }

        // Buscar la venta
        $venta = DB::table('ventas')->where('referencia_transferencia', $referencia)->first();
        if (!$venta) {
            Log::warning('[Fintoc] Referencia no encontrada en ventas', ['referencia' => $referencia]);
            return ['ok' => false, 'mensaje' => 'Referencia no existe: ' . $referencia];
        }

        // Determinar si es abono (50%) o pago completo (100%)
        $totalPagar  = (int) $venta->total_pagar;
        $abono50     = (int) ceil($totalPagar / 2);
        $tolerancia  = 500; // ±$500 de tolerancia

        $esAbono      = abs($monto - $abono50)   <= $tolerancia;
        $esPagoTotal  = abs($monto - $totalPagar) <= $tolerancia;

        if (!$esAbono && !$esPagoTotal) {
            Log::warning('[Fintoc] Monto no coincide con abono ni total', [
                'referencia'  => $referencia,
                'monto'       => $monto,
                'abono_50'    => $abono50,
                'total_pagar' => $totalPagar,
            ]);
            // Igual registramos el monto recibido, pero con estado especial
            DB::table('ventas')->where('id', $venta->id)->update([
                'monto_pagado' => $monto,
                'metodo_pago'  => 'transferencia',
                'estado_pago'  => 'monto_incorrecto',
                'confirmado_en'=> now(),
                'updated_at'   => now(),
            ]);
            return ['ok' => false, 'mensaje' => 'Monto no coincide. Requiere revisión manual.'];
        }

        $estadoPago = $esPagoTotal ? 'pago_completo' : 'abono_recibido';

        // Actualizar venta
        DB::table('ventas')->where('id', $venta->id)->update([
            'abono_programa' => $monto,
            'monto_pagado'   => $monto,
            'metodo_pago'    => 'transferencia',
            'estado_pago'    => $estadoPago,
            'confirmado_en'  => now(),
            'updated_at'     => now(),
        ]);

        // Actualizar estado de la reserva
        $estadoReserva = $esPagoTotal ? 'pagado' : 'pago_parcial';
        DB::table('reservas')->where('id', $venta->id_reserva)->update([
            'estado'     => $estadoReserva,
            'updated_at' => now(),
        ]);

        Log::info('[Fintoc] Pago registrado correctamente', [
            'venta_id'    => $venta->id,
            'referencia'  => $referencia,
            'monto'       => $monto,
            'estado_pago' => $estadoPago,
            'sender'      => $sender,
        ]);

        return [
            'ok'      => true,
            'mensaje' => "Pago registrado: {$estadoPago} — \${$monto} de {$sender}",
        ];
    }

    /**
     * Extrae el código de referencia BTC-xxx-xxxxxx de un texto.
     */
    private static function extractReference(string $text): ?string
    {
        if (preg_match('/(BTC-\d+-\d{6})/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }
        return null;
    }
}
