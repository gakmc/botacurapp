<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcesarOrdenWoocommerce;
use App\WoocommerceOrder;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WoocommerceWebhookController extends Controller
{
    // public function ping() {
    //     return response()->json([
    //         'status' => 'ok',
    //         'mensaje'   => 'Servidor Laravel respondiendo correctamente',
    //         'timestamp' => now()->toIso8601String(),
    //         'ambiente'  => app()->environment(),
    //     ]);
    // }

    /**
    * POST /api/woocommerce/webhook
    * Por ahora solo registra el payload en el log para verificar que llega.
    */
    // public function handle(Request $request)
    // {
    //     Log::info('[WC-Webhook] Payload recibido', [
    //         'headers' => $request->headers->all(),
    //         'body'    => $request->all(),
    //     ]);

    //     return response()->json(['message' => 'Recibido'], 200);
    // }





        /**
     * POST /api/woocommerce/webhook
     *
     * Recibe el webhook de WooCommerce (order.completed).
     * Valida firma HMAC → guarda log completo → despacha Job → responde 200.
     *
     * Campos Transbank confirmados en pedido real #9358:
     *   meta_data: billing_fecha_visita, authorizationCode, cardNumber,
     *              paymentType, transactionStatus, buyOrder,
     *              installmentsNumber, amount, transactionDate
     */
    public function handle(Request $request)
    {
        // ── 1. Validar firma HMAC ────────────────────────────────
        if (!$this->validarFirma($request)) {
            Log::warning('[WC-Webhook] Firma inválida. IP: ' . $request->ip());
            return response()->json(['error' => 'Firma inválida'], 401);
        }

        $payload   = $request->all();
        $wcOrderId = $payload['id']     ?? null;
        $status    = $payload['status'] ?? null;

        Log::info("[WC-Webhook] ← Recibido | Orden #{$wcOrderId} | Estado: {$status}");

        // ── 2. Solo procesar pedidos completados ─────────────────
        if ($status !== 'completed') {
            Log::info("[WC-Webhook] Ignorado (estado: {$status})");
            return response()->json(['message' => 'Estado ignorado: ' . $status], 200);
        }

        // ── 3. Idempotencia ──────────────────────────────────────
        if (WoocommerceOrder::where('wc_order_id', $wcOrderId)->exists()) {
            Log::info("[WC-Webhook] Orden #{$wcOrderId} ya recibida. Duplicado ignorado.");
            return response()->json(['message' => 'Orden ya procesada'], 200);
        }

        // ── 4. Parsear datos para el log ─────────────────────────
        $billing   = $payload['billing']    ?? [];
        $lineItems = $payload['line_items'] ?? [];
        $meta      = $this->parsearMetaData($payload['meta_data'] ?? []);

        // fecha_visita: confirmada en meta_data con key "billing_fecha_visita"
        $fechaVisitaRaw     = $meta['billing_fecha_visita'] ?? $billing['billing_fecha_visita'] ?? null;
        $fechaReservRaw     = $meta['billing_fecha_reservacion'] ?? null;

        // ── 5. Guardar log completo ──────────────────────────────
        WoocommerceOrder::create([
            // Identificadores
            'wc_order_id'           => $wcOrderId,
            'wc_order_key'          => $payload['order_key']               ?? null,
            'wc_product_id'         => $lineItems[0]['product_id']         ?? null,

            // Cliente
            'billing_email'         => strtolower(trim($billing['email']   ?? '')),
            'billing_first_name'    => $billing['first_name']              ?? null,
            'billing_last_name'     => $billing['last_name']               ?? null,
            'billing_phone'         => $billing['phone']                   ?? null,

            // Checkout personalizado
            'fecha_visita_wc'       => $this->parsearFechaSegura($fechaVisitaRaw),
            'fecha_reservacion_wc'  => $this->parsearFechaSegura($fechaReservRaw),

            // Pedido
            'status'                => $status,
            'total'                 => (int) ($payload['total']            ?? $meta['amount'] ?? 0),
            'currency'              => $payload['currency']                ?? null,
            'payment_method'        => $payload['payment_method_title']    ?? null,

            // Transbank Webpay (confirmados en pedido real #9358)
            'authorization_code'    => $meta['authorizationCode']          ?? null,
            'card_number'           => $meta['cardNumber']                 ?? null,
            'payment_type'          => $meta['paymentType']                ?? null,
            'transaction_status'    => $meta['transactionStatus']          ?? null,
            'buy_order'             => $meta['buyOrder']                   ?? null,
            'installments_number'   => (int) ($meta['installmentsNumber']  ?? 0),

            // Estado inicial
            'procesado'             => 'pendiente',
            'payload_raw'           => $payload,
        ]);

        // ── 6. Despachar Job ─────────────────────────────────────
        ProcesarOrdenWoocommerce::dispatch($payload);

        Log::info(
            "[WC-Webhook] → Job despachado | Orden #{$wcOrderId} | " .
            "Fecha visita: {$fechaVisitaRaw} | Auth: " . ($meta['authorizationCode'] ?? 'n/a')
        );

        // ── 7. Responder 200 inmediatamente a WC ─────────────────
        return response()->json(['message' => 'Recibido'], 200);
    }

    // ─────────────────────────────────────────────────────────────
    //  GET /api/woocommerce/ping
    // ─────────────────────────────────────────────────────────────
    public function ping()
    {
        return response()->json([
            'status'    => 'ok',
            'message'   => 'Webhook endpoint activo',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  Validación firma HMAC
    // ─────────────────────────────────────────────────────────────
    private function validarFirma(Request $request): bool
    {
        $secret    = config('woocommerce.webhook_secret');
        $signature = $request->header('X-WC-Webhook-Signature');

        if (empty($secret)) {
            if (app()->environment('local')) {
                Log::warning('[WC-Webhook] Secret no configurado. Saltando validación en local.');
                return true;
            }
            Log::error('[WC-Webhook] WOOCOMMERCE_WEBHOOK_SECRET no configurado en producción.');
            return false;
        }

        if (empty($signature)) {
            return false;
        }

        $hash = base64_encode(
            hash_hmac('sha256', $request->getContent(), $secret, true)
        );

        return hash_equals($hash, $signature);
    }

    // ─────────────────────────────────────────────────────────────
    //  Helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Convierte meta_data de WC en array key → value.
     * Ignora keys internas de WordPress (empiezan con _).
     */
    private function parsearMetaData(array $metaData): array
    {
        $meta = [];
        foreach ($metaData as $item) {
            if (isset($item['key']) && strpos($item['key'], '_') !== 0) {
                $meta[$item['key']] = $item['value'] ?? null;
            }
        }
        return $meta;
    }

    private function parsearFechaSegura(?string $fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }
        try {
            // Formato confirmado: "2024-10-27" → ya es Y-m-d
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($fecha))) {
                return $fecha;
            }
            return Carbon::parse($fecha)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
