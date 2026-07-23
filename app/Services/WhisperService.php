<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

/**
 * WhisperService
 *
 * Transcribe audios de WhatsApp (OGG/Opus) usando OpenAI Whisper API.
 * El archivo ya debe estar descargado localmente antes de llamar a transcribir().
 *
 * Compatible PHP 7.2 / Laravel 6.
 */
class WhisperService
{
    /** @var string */
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', '');
    }

    public function configurado(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Transcribe un archivo de audio local y devuelve el texto.
     *
     * @param  string $rutaLocal  Ruta absoluta al archivo temporal
     * @param  string $extension  Extensión del archivo (ogg, mp4, webm, m4a, mp3, wav)
     * @return string|null        Texto transcrito, o null si falló
     */
    public function transcribir(string $rutaLocal, string $extension = 'ogg'): ?string
    {
        if (!$this->configurado()) {
            Log::warning('[Whisper] OPENAI_API_KEY no configurado — audio ignorado');
            return null;
        }

        if (!file_exists($rutaLocal) || filesize($rutaLocal) === 0) {
            Log::warning('[Whisper] Archivo de audio vacío o inexistente: ' . $rutaLocal);
            return null;
        }

        try {
            $client   = new Client(['timeout' => 60, 'http_errors' => true]);
            $response = $client->post('https://api.openai.com/v1/audio/transcriptions', [
                'headers'   => ['Authorization' => 'Bearer ' . $this->apiKey],
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($rutaLocal, 'r'),
                        'filename' => 'audio.' . $extension,
                    ],
                    ['name' => 'model',           'contents' => 'whisper-1'],
                    ['name' => 'language',        'contents' => 'es'],
                    ['name' => 'response_format', 'contents' => 'text'],
                ],
            ]);

            $texto = trim((string) $response->getBody());
            Log::info('[Whisper] Transcripción OK', ['chars' => strlen($texto)]);
            return $texto ?: null;

        } catch (\Exception $e) {
            Log::error('[Whisper] Error: ' . $e->getMessage());
            return null;
        } finally {
            // Limpiar archivo temporal
            if (file_exists($rutaLocal)) {
                @unlink($rutaLocal);
            }
        }
    }
}
