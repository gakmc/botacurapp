<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppWebhookController
 *
 * GET  /api/whatsapp/webhook  → verificación Meta
 * POST /api/whatsapp/webhook  → mensajes entrantes → BotController@message
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class WhatsAppWebhookController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/whatsapp/webhook
    // Meta envía: hub.mode=subscribe, hub.verify_token=..., hub.challenge=...
    // ─────────────────────────────────────────────────────────────────────────
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode')         ?? $request->query('hub.mode');
        $token     = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
        $challenge = $request->query('hub_challenge')    ?? $request->query('hub.challenge');

        $verifyToken = env('META_VERIFY_TOKEN', 'botacura_webhook_verify_2024');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('[WhatsApp] Webhook verificado correctamente');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('[WhatsApp] Verificación fallida', ['mode' => $mode, 'token' => $token]);
        return response('Forbidden', 403);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /api/whatsapp/webhook
    // Recibe mensajes de WhatsApp y los pasa al BotController
    // ─────────────────────────────────────────────────────────────────────────
    public function handle(Request $request)
    {
        $body = $request->all();

        // Verificar que sea un evento de WhatsApp Business
        if (($body['object'] ?? '') !== 'whatsapp_business_account') {
            return response()->json(['ok' => true]);
        }

        try {
            $entry   = $body['entry'][0]    ?? null;
            $changes = $entry['changes'][0] ?? null;
            $value   = $changes['value']    ?? null;

            if (!$value) {
                return response()->json(['ok' => true]);
            }

            // Solo procesar mensajes entrantes (ignorar status updates)
            $messages = $value['messages'] ?? [];
            if (empty($messages)) {
                return response()->json(['ok' => true]);
            }

            $msg      = $messages[0];
            $tipo     = $msg['type'] ?? '';
            $telefono = $msg['from'] ?? '';
            $nombre   = $value['contacts'][0]['profile']['name'] ?? 'Cliente';

            // Solo procesar mensajes de texto por ahora
            if ($tipo !== 'text') {
                Log::info("[WhatsApp] Tipo no soportado: {$tipo} de {$telefono}");
                return response()->json(['ok' => true]);
            }

            $texto = $msg['text']['body'] ?? '';
            if (!$texto) {
                return response()->json(['ok' => true]);
            }

            Log::info("[WhatsApp] Mensaje recibido de {$telefono}: {$texto}");

            // Llamar al bot internamente
            $secret = config('services.bot.secret');
            $botRes = Http::withHeaders([
                'X-Bot-Secret'  => $secret,
                'Content-Type'  => 'application/json',
            ])->timeout(35)->post(url('/api/bot-ai/message'), [
                'telefono' => $telefono,
                'mensaje'  => $texto,
                'nombre'   => $nombre,
            ]);

            $botData = $botRes->json();
            $mensaje = $botData['mensaje'] ?? null;

            // Enviar respuesta por WhatsApp
            if ($mensaje) {
                $this->enviarMensaje($telefono, $mensaje);
            }

            // Si el bot adjunta archivos (PDF del menú)
            if (!empty($botData['adjunto_url'])) {
                $this->enviarDocumento($telefono, $botData['adjunto_url'], $botData['adjunto_nombre'] ?? 'menu.pdf');
            }

        } catch (\Exception $e) {
            Log::error('[WhatsApp] Error procesando webhook: ' . $e->getMessage());
        }

        // Meta espera siempre 200
        return response()->json(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers — WhatsApp Cloud API
    // ─────────────────────────────────────────────────────────────────────────

    private function enviarMensaje(string $telefono, string $texto)
    {
        $phoneId = env('META_PHONE_NUMBER_ID');
        $token   = env('META_WHATSAPP_TOKEN');
        $version = env('META_API_VERSION', 'v19.0');

        try {
            $res = Http::withToken($token)
                ->timeout(10)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $telefono,
                    'type'              => 'text',
                    'text'              => ['body' => $texto],
                ]);

            if (!$res->successful()) {
                Log::error('[WhatsApp] Error enviando mensaje', ['status' => $res->status(), 'body' => $res->body()]);
            }
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Excepción enviando mensaje: ' . $e->getMessage());
        }
    }

    private function enviarDocumento(string $telefono, string $url, string $nombre)
    {
        $phoneId = env('META_PHONE_NUMBER_ID');
        $token   = env('META_WHATSAPP_TOKEN');
        $version = env('META_API_VERSION', 'v19.0');

        try {
            Http::withToken($token)
                ->timeout(10)
                ->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to'                => $telefono,
                    'type'              => 'document',
                    'document'          => [
                        'link'     => $url,
                        'filename' => $nombre,
                    ],
                ]);
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Excepción enviando documento: ' . $e->getMessage());
        }
    }
}
