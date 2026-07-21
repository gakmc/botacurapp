<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

/**
 * WebpayService — Transbank Webpay Plus (REST API directo, sin SDK)
 *
 * Variables .env requeridas:
 *   WEBPAY_ENV=sandbox           # 'sandbox' | 'production'
 *   WEBPAY_COMMERCE_CODE=597055555532
 *   WEBPAY_API_KEY=579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C
 *
 * Credenciales sandbox por defecto (Transbank):
 *   commerce_code : 597055555532
 *   api_key       : 579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C
 *
 * Flujo:
 *   1. initTransaction($monto, $buyOrder, $sessionId, $returnUrl)
 *      → devuelve ['token'=>..., 'url'=>...], redirigir usuario a url?token_ws=token
 *   2. Usuario paga en Transbank; Transbank redirige a $returnUrl?token_ws=TOKEN
 *   3. commitTransaction($token) → confirma el pago, devuelve detalles
 */
class WebpayService
{
    /** @var string */
    private $baseUrl;
    /** @var string */
    private $commerceCode;
    /** @var string */
    private $apiKey;
    /** @var Client */
    private $client;

    // Endpoints REST Webpay Plus
    const ENDPOINT_SANDBOX    = 'https://webpay3gint.transbank.cl';
    const ENDPOINT_PRODUCTION = 'https://webpay3g.transbank.cl';
    const PATH_TRANSACTIONS   = '/rswebpaytransaction/api/webpay/v1.2/transactions';

    public function __construct()
    {
        $env = env('WEBPAY_ENV', 'sandbox');

        $this->baseUrl      = ($env === 'production')
            ? self::ENDPOINT_PRODUCTION
            : self::ENDPOINT_SANDBOX;

        // Credenciales sandbox por defecto si no están configuradas en .env
        $this->commerceCode = env('WEBPAY_COMMERCE_CODE', '597055555532');
        $this->apiKey       = env('WEBPAY_API_KEY',
            '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C');

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 30,
            'headers'  => [
                'Tbk-Api-Key-Id'     => $this->commerceCode,
                'Tbk-Api-Key-Secret' => $this->apiKey,
                'Content-Type'       => 'application/json',
            ],
        ]);
    }

    /**
     * Inicia una transacción Webpay Plus.
     *
     * @param  int    $monto      Monto en pesos CLP (sin decimales)
     * @param  string $buyOrder   Orden de compra (máx 26 chars, único por sesión)
     * @param  string $sessionId  ID de sesión (máx 61 chars)
     * @param  string $returnUrl  URL de retorno tras el pago
     * @return array  ['ok'=>true, 'token'=>'...', 'url'=>'...'] | ['ok'=>false, 'error'=>'...']
     */
    public function initTransaction(int $monto, string $buyOrder, string $sessionId, string $returnUrl): array
    {
        try {
            $response = $this->client->post(self::PATH_TRANSACTIONS, [
                'json' => [
                    'buy_order'  => substr($buyOrder, 0, 26),
                    'session_id' => substr($sessionId, 0, 61),
                    'amount'     => $monto,
                    'return_url' => $returnUrl,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            Log::info('[Webpay] initTransaction OK', [
                'buy_order'  => $buyOrder,
                'monto'      => $monto,
                'token'      => $data['token'] ?? null,
            ]);

            return [
                'ok'    => true,
                'token' => $data['token'],
                'url'   => $data['url'],
            ];
        } catch (RequestException $e) {
            $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            Log::error('[Webpay] initTransaction error', [
                'buy_order' => $buyOrder,
                'status'    => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0,
                'body'      => $body,
            ]);
            return ['ok' => false, 'error' => $body ?: $e->getMessage()];
        }
    }

    /**
     * Confirma (commit) una transacción tras el retorno del usuario.
     * Debe llamarse UNA sola vez con el token_ws recibido en el returnUrl.
     *
     * @param  string $token  token_ws recibido en el redirect de vuelta
     * @return array  ['ok'=>true, 'data'=>[...]] | ['ok'=>false, 'error'=>'...']
     *
     * Campos clave en data:
     *   vci              : 'TSY' = aprobado
     *   status           : 'AUTHORIZED'
     *   response_code    : 0 = aprobado
     *   amount           : monto autorizado
     *   buy_order
     *   session_id
     *   card_detail.card_number : últimos 4 dígitos
     *   transaction_date : ISO 8601
     *   authorization_code
     */
    public function commitTransaction(string $token): array
    {
        try {
            $response = $this->client->put(self::PATH_TRANSACTIONS . '/' . $token);
            $data     = json_decode($response->getBody()->getContents(), true);

            $aprobado = isset($data['response_code']) && (int)$data['response_code'] === 0
                        && ($data['status'] ?? '') === 'AUTHORIZED';

            Log::info('[Webpay] commitTransaction', [
                'token'         => $token,
                'status'        => $data['status'] ?? null,
                'response_code' => $data['response_code'] ?? null,
                'aprobado'      => $aprobado,
            ]);

            return [
                'ok'       => true,
                'aprobado' => $aprobado,
                'data'     => $data,
            ];
        } catch (RequestException $e) {
            $body = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            Log::error('[Webpay] commitTransaction error', [
                'token'  => $token,
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 0,
                'body'   => $body,
            ]);
            return ['ok' => false, 'error' => $body ?: $e->getMessage()];
        }
    }

    /**
     * Consulta el estado de una transacción (sin confirmar).
     */
    public function getStatus(string $token): array
    {
        try {
            $response = $this->client->get(self::PATH_TRANSACTIONS . '/' . $token);
            $data     = json_decode($response->getBody()->getContents(), true);
            return ['ok' => true, 'data' => $data];
        } catch (RequestException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** Retorna true si está en modo sandbox */
    public function isSandbox(): bool
    {
        return env('WEBPAY_ENV', 'sandbox') !== 'production';
    }
}
