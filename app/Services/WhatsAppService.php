<?php

namespace App\Services;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppService
 *
 * Envío de mensajes de texto vía WhatsApp Cloud API (Meta).
 * Centraliza lo que antes estaba duplicado en PagoController y
 * VerificacionPagoController.
 */
class WhatsAppService
{
    public function enviarMensaje(string $telefono, string $texto): bool
    {
        $phoneId = env('META_PHONE_NUMBER_ID');
        $token   = env('META_WHATSAPP_TOKEN');
        $version = env('META_API_VERSION', 'v19.0');

        try {
            $client = new GuzzleClient(['timeout' => 10, 'http_errors' => false]);
            $res    = $client->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to'                => $telefono,
                    'type'              => 'text',
                    'text'              => ['body' => $texto],
                ],
            ]);

            if ($res->getStatusCode() >= 300) {
                Log::error('[WhatsAppService] Error enviando mensaje', [
                    'status' => $res->getStatusCode(),
                    'body'   => (string) $res->getBody(),
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('[WhatsAppService] Excepción enviando mensaje: ' . $e->getMessage());
            return false;
        }
    }
}
