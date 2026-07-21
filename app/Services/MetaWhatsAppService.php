<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * MetaWhatsAppService
 *
 * Envía mensajes y acciones a través de la WhatsApp Cloud API de Meta.
 * Compatible Laravel 6 / PHP 7.2
 */
class MetaWhatsAppService
{
    /** @var string */
    private $phoneNumberId;

    /** @var string */
    private $token;

    /** @var string */
    private $apiVersion;

    public function __construct()
    {
        $this->phoneNumberId = env('META_PHONE_NUMBER_ID', '');
        $this->token         = env('META_WHATSAPP_TOKEN', '');
        $this->apiVersion    = env('META_API_VERSION', 'v19.0');
    }

    /**
     * Envía un mensaje de texto al número indicado.
     *
     * @param  string $to      Número en formato internacional sin + (ej: 56912345678)
     * @param  string $mensaje Texto del mensaje
     * @return array|null
     */
    public function sendMessage(string $to, string $mensaje)
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 10]);
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => ['body' => $mensaje],
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            Log::info("[MetaWA] Mensaje enviado a {$to}: " . ($data['messages'][0]['id'] ?? 'sin id'));
            return $data;

        } catch (\Exception $e) {
            Log::error("[MetaWA] Error enviando a {$to}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Marca un mensaje recibido como leído (muestra el check azul).
     *
     * @param  string $messageId  ID del mensaje de Meta
     */
    public function markAsRead(string $messageId)
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 5]);
            $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'status'            => 'read',
                    'message_id'        => $messageId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning("[MetaWA] Error marcando como leído {$messageId}: " . $e->getMessage());
        }
    }

    /**
     * Sube un archivo a la WhatsApp Media API y devuelve el media_id.
     *
     * @param  string $contenido    Contenido binario del archivo
     * @param  string $mimeType     MIME type (ej: application/pdf)
     * @param  string $filename     Nombre del archivo (ej: confirmacion.pdf)
     * @return string|null          media_id o null si falla
     */
    public function uploadMedia(string $contenido, string $mimeType, string $filename): ?string
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/media";

        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 30]);
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
                'multipart' => [
                    [
                        'name'     => 'messaging_product',
                        'contents' => 'whatsapp',
                    ],
                    [
                        'name'     => 'type',
                        'contents' => $mimeType,
                    ],
                    [
                        'name'     => 'file',
                        'contents' => $contenido,
                        'filename' => $filename,
                        'headers'  => ['Content-Type' => $mimeType],
                    ],
                ],
            ]);

            $data    = json_decode($response->getBody()->getContents(), true);
            $mediaId = $data['id'] ?? null;

            if ($mediaId) {
                Log::info("[MetaWA] Media subida OK: {$mediaId} ({$filename})");
            } else {
                Log::warning("[MetaWA] uploadMedia sin id en respuesta: " . json_encode($data));
            }

            return $mediaId;

        } catch (\Exception $e) {
            Log::error("[MetaWA] Error subiendo media ({$filename}): " . $e->getMessage());
            return null;
        }
    }

    /**
     * Envía un documento (ya subido via uploadMedia) al número indicado.
     *
     * @param  string $to        Número destinatario (sin +)
     * @param  string $mediaId   ID obtenido de uploadMedia()
     * @param  string $filename  Nombre que verá el receptor
     * @param  string $caption   Texto acompañante (opcional)
     * @return array|null
     */
    public function sendDocument(string $to, string $mediaId, string $filename, string $caption = ''): ?array
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";

        $doc = ['id' => $mediaId, 'filename' => $filename];
        if ($caption !== '') {
            $doc['caption'] = $caption;
        }

        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 15]);
            $response = $client->post($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'recipient_type'    => 'individual',
                    'to'                => $to,
                    'type'              => 'document',
                    'document'          => $doc,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            Log::info("[MetaWA] Documento enviado a {$to}: " . ($data['messages'][0]['id'] ?? 'sin id'));
            return $data;

        } catch (\Exception $e) {
            Log::error("[MetaWA] Error enviando documento a {$to}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica que las credenciales estén configuradas.
     */
    public function isConfigured()
    {
        return !empty($this->phoneNumberId) && !empty($this->token);
    }
}
