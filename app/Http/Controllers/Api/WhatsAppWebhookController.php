<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MetaWhatsAppService;
use App\Services\ReservaPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * WhatsAppWebhookController
 *
 * Maneja el webhook de WhatsApp Cloud API (Meta).
 *
 * Rutas:
 *   GET  /api/whatsapp/webhook  → verificación de webhook por Meta
 *   POST /api/whatsapp/webhook  → mensajes entrantes de WhatsApp
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class WhatsAppWebhookController extends Controller
{
    /** @var MetaWhatsAppService */
    private $whatsApp;

    public function __construct(MetaWhatsAppService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/whatsapp/webhook
    // Meta llama a este endpoint para verificar el webhook.
    // Devuelve hub.challenge si el token coincide.
    // ─────────────────────────────────────────────────────────────
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = env('META_VERIFY_TOKEN', '');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            Log::info('[WhatsApp Webhook] Verificación exitosa.');
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning("[WhatsApp Webhook] Verificación fallida. Token recibido: {$token}");
        return response('Forbidden', 403);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/whatsapp/webhook
    // Recibe mensajes entrantes de WhatsApp.
    // SIEMPRE devuelve 200 OK a Meta (reintenta si no recibe 200).
    // ─────────────────────────────────────────────────────────────
    public function receive(Request $request)
    {
        $body = $request->json()->all();

        // Meta siempre debe recibir 200 — procesar en try/catch
        try {
            // Validar que sea evento de WhatsApp Business
            if (($body['object'] ?? '') !== 'whatsapp_business_account') {
                return response('OK', 200);
            }

            $entry   = $body['entry'][0] ?? null;
            $change  = $entry['changes'][0] ?? null;
            $value   = $change['value'] ?? null;

            if (!$value || !isset($value['messages'])) {
                return response('OK', 200);
            }

            $message = $value['messages'][0];
            $msgId   = $message['id'] ?? '';

            // Deduplicación: evitar procesar el mismo mensaje dos veces
            $cacheKey = 'wa_msg_' . md5($msgId);
            if (Cache::has($cacheKey)) {
                Log::info("[WhatsApp Webhook] Mensaje duplicado ignorado: {$msgId}");
                return response('OK', 200);
            }
            Cache::put($cacheKey, true, 300); // 5 minutos

            // Solo mensajes de texto por ahora
            if (($message['type'] ?? '') !== 'text') {
                Log::info("[WhatsApp Webhook] Tipo de mensaje no soportado: " . ($message['type'] ?? 'unknown'));
                $from = $message['from'] ?? '';
                if ($from) {
                    $this->whatsApp->sendMessage($from,
                        'Por el momento solo puedo responder mensajes de texto 🙏 ' .
                        "Escríbeme lo que necesitas y con gusto te ayudo 💚"
                    );
                }
                return response('OK', 200);
            }

            $from    = $message['from'];                              // "56912345678"
            $texto   = $message['text']['body'] ?? '';
            $nombre  = $value['contacts'][0]['profile']['name'] ?? '';

            if (empty($texto)) {
                return response('OK', 200);
            }

            Log::info("[WhatsApp Webhook] Mensaje de {$from} ({$nombre}): {$texto}");

            // Marcar como leído (check azul)
            $this->whatsApp->markAsRead($msgId);

            // Llamar al bot (endpoint existente)
            $botResponse = $this->llamarBot($from, $texto, $nombre);

            // Enviar respuesta al cliente
            $mensajeBot = $botResponse['mensaje'] ?? null;
            if ($mensajeBot) {
                $this->whatsApp->sendMessage($from, $mensajeBot);
                Log::info("[WhatsApp Webhook] Respuesta enviada a {$from}. Accion: " . ($botResponse['accion'] ?? '?'));
            } else {
                Log::warning("[WhatsApp Webhook] Bot no devolvió mensaje para {$from}.");
            }

            // Si se creó una reserva, enviar también el PDF de confirmación
            $reservaId = $botResponse['datos']['reserva_id'] ?? null;
            if ($reservaId) {
                $this->enviarPdfConfirmacion($from, (int) $reservaId);
            }

            // Si el bot eligió actualizar tipo_servicio post-pago
            if (($botResponse['accion'] ?? '') === 'actualizar_tipo_servicio') {
                $this->ejecutarActualizarTipoServicio($botResponse['datos'] ?? []);
            }

            // Si el bot recopiló elecciones de menú
            if (($botResponse['accion'] ?? '') === 'actualizar_menu') {
                $this->ejecutarActualizarMenu($botResponse['datos'] ?? []);
            }

        } catch (\Exception $e) {
            Log::error('[WhatsApp Webhook] Error: ' . $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine());
        }

        return response('OK', 200);
    }

    // ─────────────────────────────────────────────────────────────
    // Actualiza tipo_servicio en todos los menús de la reserva.
    // ─────────────────────────────────────────────────────────────
    private function ejecutarActualizarTipoServicio(array $datos): void
    {
        $reservaId    = $datos['reserva_id']    ?? null;
        $tipoServicio = $datos['tipo_servicio'] ?? null;

        if (!$reservaId || !$tipoServicio) {
            Log::warning('[WhatsApp Webhook] actualizar_tipo_servicio: datos incompletos', $datos);
            return;
        }

        try {
            $url    = 'http://127.0.0.1/api/bot/reserva/' . (int)$reservaId . '/tipo-servicio';
            $secret = env('BOT_SECRET', '');
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $client->patch($url, [
                'headers' => [
                    'X-Bot-Secret' => $secret,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => ['tipo_servicio' => $tipoServicio],
            ]);
            Log::info("[WhatsApp Webhook] tipo_servicio actualizado: reserva {$reservaId} → {$tipoServicio}");
        } catch (\Exception $e) {
            Log::error('[WhatsApp Webhook] Error actualizando tipo_servicio: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Guarda elecciones de menú por persona llamando al endpoint interno.
    // ─────────────────────────────────────────────────────────────
    private function ejecutarActualizarMenu(array $datos): void
    {
        $reservaId  = $datos['reserva_id']  ?? null;
        $todosIgual = $datos['todos_igual'] ?? false;

        if (!$reservaId) {
            Log::warning('[WhatsApp Webhook] actualizar_menu: reserva_id faltante', $datos);
            return;
        }

        try {
            $url    = 'http://127.0.0.1/api/bot/reserva/' . (int)$reservaId . '/menu';
            $secret = env('BOT_SECRET', '');
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            $client->patch($url, [
                'headers' => [
                    'X-Bot-Secret' => $secret,
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $datos,
            ]);
            Log::info("[WhatsApp Webhook] Menú actualizado: reserva {$reservaId}");
        } catch (\Exception $e) {
            Log::error('[WhatsApp Webhook] Error actualizando menú: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Genera el PDF de confirmación de reserva y lo envía por WA.
    // Falla de forma silenciosa — el mensaje de texto ya fue enviado.
    // ─────────────────────────────────────────────────────────────
    private function enviarPdfConfirmacion(string $to, int $reservaId): void
    {
        try {
            $pdfContent = ReservaPdfService::generarPdf($reservaId);
            if (!$pdfContent) {
                Log::warning("[WhatsApp Webhook] No se pudo generar PDF para reserva {$reservaId}");
                return;
            }

            $filename = "Confirmacion_Reserva_Botacura_{$reservaId}.pdf";
            $mediaId  = $this->whatsApp->uploadMedia($pdfContent, 'application/pdf', $filename);

            if (!$mediaId) {
                Log::warning("[WhatsApp Webhook] No se pudo subir PDF de reserva {$reservaId}");
                return;
            }

            $this->whatsApp->sendDocument(
                $to,
                $mediaId,
                $filename,
                '📄 Aquí tienes tu confirmación de reserva. Guárdala, contiene los datos de pago.'
            );

            Log::info("[WhatsApp Webhook] PDF de reserva {$reservaId} enviado a {$to}");

        } catch (\Exception $e) {
            Log::error("[WhatsApp Webhook] Error enviando PDF reserva {$reservaId}: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    // Llama al endpoint /api/bot/message internamente vía Guzzle.
    // Reutiliza toda la lógica de Claude + historial + reservas.
    // ─────────────────────────────────────────────────────────────
    private function llamarBot(string $telefono, string $mensaje, string $nombre)
    {
        $secret = env('BOT_SECRET', '');
        // Llamada directa a 127.0.0.1 para no pasar por localtunnel/ngrok en dev
        $url    = 'http://127.0.0.1/api/bot/message';

        try {
            $client   = new \GuzzleHttp\Client(['timeout' => 45]);
            $response = $client->post($url, [
                'headers' => [
                    'X-Bot-Secret'  => $secret,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'telefono' => $telefono,
                    'mensaje'  => $mensaje,
                    'nombre'   => $nombre,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];

        } catch (\Exception $e) {
            Log::error('[WhatsApp Webhook] Error llamando al bot: ' . $e->getMessage());
            return ['mensaje' => 'Lo sentimos, ocurrió un error. Por favor escribe a +56 9 7448 4112 💚'];
        }
    }
}
