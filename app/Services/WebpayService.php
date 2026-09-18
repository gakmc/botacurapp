<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

/**
 * WebpayService — Transbank Webpay Plus REST v1.2
 *
 * Flujo:
 * 1. iniciarTransaccion() → retorna ['url' => ..., 'token' => ...]
 * 2. Cliente paga en URL de Transbank
 * 3. Transbank redirige a return_url con token_ws
 * 4. confirmarTransaccion($token) → retorna datos del pago
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class WebpayService
{
    private $env;
    private $commerceCode;
    private $apiKey;
    private $baseUrl;

    const URL_SANDBOX    = 'https://webpay3gint.transbank.cl/rswebpaytransaction/api/webpay/v1.2/transactions';
    const URL_PRODUCTION = 'https://webpay3g.transbank.cl/rswebpaytransaction/api/webpay/v1.2/transactions';

    public function __construct()
    {
        $this->env          = env('WEBPAY_ENV', 'sandbox');
        $this->commerceCode = env('WEBPAY_COMMERCE_CODE', '597055555532');
        $this->apiKey       = env('WEBPAY_API_KEY', '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C');
        $this->baseUrl      = ($this->env === 'production') ? self::URL_PRODUCTION : self::URL_SANDBOX;
    }

    /**
     * Inicia una transacción Webpay Plus.
     *
     * @param  int    $ventaId     ID de la venta (buy_order)
     * @param  int    $reservaId   ID de la reserva (session_id)
     * @param  int    $amount      Monto en pesos chilenos (sin decimales)
     * @param  string $returnUrl   URL a la que Transbank redirige tras el pago
     * @return array|null ['token' => string, 'url' => string] o null si falla
     */
    public function iniciarTransaccion(int $ventaId, int $reservaId, int $amount, string $returnUrl): ?array
    {
        try {
            $buyOrder  = 'BOT-' . $ventaId . '-' . time();
            $sessionId = 'RES-' . $reservaId;

            $client = new GuzzleClient(['timeout' => 15, 'http_errors' => false]);

            $response = $client->post($this->baseUrl, [
                'headers' => [
                    'Tbk-Api-Key-Id'     => $this->commerceCode,
                    'Tbk-Api-Key-Secret' => $this->apiKey,
                    'Content-Type'       => 'application/json',
                ],
                'json' => [
                    'buy_order'  => $buyOrder,
                    'session_id' => $sessionId,
                    'amount'     => $amount,
                    'return_url' => $returnUrl,
                ],
            ]);

            $status = $response->getStatusCode();
            $body   = json_decode((string) $response->getBody(), true) ?? [];

            if ($status !== 200 || empty($body['token']) || empty($body['url'])) {
                Log::error('[Webpay] Error al iniciar transacción', [
                    'status'   => $status,
                    'body'     => $body,
                    'venta_id' => $ventaId,
                ]);
                return null;
            }

            Log::info('[Webpay] Transacción iniciada', [
                'venta_id'  => $ventaId,
                'buy_order' => $buyOrder,
                'amount'    => $amount,
                'token'     => substr($body['token'], 0, 10) . '...',
            ]);

            return [
                'token' => $body['token'],
                'url'   => $body['url'] . '?token_ws=' . $body['token'],
            ];

        } catch (\Exception $e) {
            Log::error('[Webpay] Excepción al iniciar transacción: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Confirma una transacción Webpay Plus (PUT al token).
     * Llamar desde el return_url con el token_ws recibido.
     *
     * @param  string $token  token_ws recibido del redirect
     * @return array|null  Datos del pago o null si falla
     */
    public function confirmarTransaccion(string $token): ?array
    {
        try {
            $client = new GuzzleClient(['timeout' => 15, 'http_errors' => false]);

            $response = $client->put($this->baseUrl . '/' . $token, [
                'headers' => [
                    'Tbk-Api-Key-Id'     => $this->commerceCode,
                    'Tbk-Api-Key-Secret' => $this->apiKey,
                    'Content-Type'       => 'application/json',
                ],
            ]);

            $status = $response->getStatusCode();
            $body   = json_decode((string) $response->getBody(), true) ?? [];

            Log::info('[Webpay] Confirmación', [
                'status'        => $status,
                'response_code' => $body['response_code'] ?? 'N/A',
                'token'         => substr($token, 0, 10) . '...',
            ]);

            if ($status !== 200) {
                return null;
            }

            return $body;

        } catch (\Exception $e) {
            Log::error('[Webpay] Excepción al confirmar transacción: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retorna true si el resultado de confirmarTransaccion() indica pago aprobado.
     * response_code === 0 → aprobado.
     */
    public function esAprobado(array $confirmacion): bool
    {
        return isset($confirmacion['response_code']) && $confirmacion['response_code'] === 0;
    }
}
