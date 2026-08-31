<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WhisperService;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * WhatsAppWebhookController
 *
 * GET  /api/whatsapp/webhook  → verificación Meta
 * POST /api/whatsapp/webhook  → mensajes entrantes → BotController@message
 *
 * Tipos soportados:
 *   text  → directo al bot
 *   audio → Whisper transcribe → bot
 *   image → Claude Vision describe → bot
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class WhatsAppWebhookController extends Controller
{
    /**
     * phone_number_id del número de Meta que recibió el mensaje actual
     * (metadata.phone_number_id del webhook). Se usa para responder desde
     * el mismo número al que escribió el cliente, en vez de un número fijo.
     * Si no viene en el payload, cae a env('META_PHONE_NUMBER_ID').
     */
    private $phoneNumberId = null;

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/whatsapp/webhook  — verificación Meta
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
    // POST /api/whatsapp/webhook — mensajes entrantes
    // ─────────────────────────────────────────────────────────────────────────

    public function handle(Request $request)
    {
        $body = $request->all();

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

            // Número de Meta que recibió este mensaje — las respuestas salen
            // por este mismo número (fallback al fijo de .env si no viene).
            $this->phoneNumberId = $value['metadata']['phone_number_id'] ?? null;

            $messages = $value['messages'] ?? [];
            if (empty($messages)) {
                return response()->json(['ok' => true]);
            }

            $msg      = $messages[0];
            $tipo     = $msg['type'] ?? '';
            $telefono = $msg['from'] ?? '';
            $nombre   = $value['contacts'][0]['profile']['name'] ?? 'Cliente';

            Log::info("[WhatsApp] Mensaje tipo={$tipo} de {$telefono}");

            $textoParaBot = null;

            // ── Texto ─────────────────────────────────────────────────────
            if ($tipo === 'text') {
                $textoParaBot = $msg['text']['body'] ?? '';

            // ── Audio ─────────────────────────────────────────────────────
            } elseif ($tipo === 'audio') {
                $textoParaBot = $this->procesarAudio($msg, $telefono);

            // ── Imagen ────────────────────────────────────────────────────
            } elseif ($tipo === 'image') {
                $textoParaBot = $this->procesarImagen($msg, $telefono);

            // ── Sticker (ignorar silenciosamente) ─────────────────────────
            } elseif ($tipo === 'sticker') {
                Log::info("[WhatsApp] Sticker ignorado de {$telefono}");

            // ── Tipos no soportados ────────────────────────────────────────
            } else {
                Log::info("[WhatsApp] Tipo no soportado: {$tipo} de {$telefono}");
                $this->enviarMensaje($telefono,
                    "Por ahora solo puedo procesar mensajes de texto, audios e imágenes 😊"
                );
            }

            if (!$textoParaBot) {
                return response()->json(['ok' => true]);
            }

            // ── Llamar al bot ──────────────────────────────────────────────
            $secret = config('services.bot.secret');
            $botToken = env('BOT_API_TOKEN');
            $client = new GuzzleClient(['timeout' => 35, 'http_errors' => false]);
            $botRes = $client->post(url('/api/bot-ai/message'), [
                'headers' => [
                    'X-Bot-Secret' => $secret,
                    'X-Bot-Token'  => $botToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'telefono' => $telefono,
                    'mensaje'  => $textoParaBot,
                    'nombre'   => $nombre,
                ],
            ]);

            $botStatus = $botRes->getStatusCode();
            $botBody   = (string) $botRes->getBody();
            Log::info('[WhatsApp] Bot response', ['status' => $botStatus, 'body' => substr($botBody, 0, 500)]);

            $botData = json_decode($botBody, true) ?? [];
            $mensaje = $botData['mensaje'] ?? null;

            if ($mensaje) {
                $this->enviarMensaje($telefono, $mensaje);
            }

            if (!empty($botData['adjunto_url'])) {
                $this->enviarDocumento($telefono, $botData['adjunto_url'], $botData['adjunto_nombre'] ?? 'menu.pdf');
            }

        } catch (\Exception $e) {
            Log::error('[WhatsApp] Error procesando webhook: ' . $e->getMessage());
        }

        return response()->json(['ok' => true]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AUDIO — descarga + Whisper
    // ─────────────────────────────────────────────────────────────────────────

    private function procesarAudio(array $msg, string $telefono): ?string
    {
        $mediaId = $msg['audio']['id'] ?? null;
        if (!$mediaId) {
            return null;
        }

        $whisper = new WhisperService();

        if (!$whisper->configurado()) {
            Log::warning('[WhatsApp] Whisper no configurado — audio de ' . $telefono . ' ignorado');
            $this->enviarMensaje($telefono,
                "Recibí tu audio 🎤 pero aún no tengo activada la transcripción. "
                . "Por favor escríbeme tu consulta en texto 😊"
            );
            return null;
        }

        // Descargar audio de Meta
        $extension  = $msg['audio']['mime_type'] ?? 'audio/ogg';
        $extension  = $this->mimeToExtension($extension);
        $rutaTemp   = $this->descargarMedia($mediaId, $extension);

        if (!$rutaTemp) {
            $this->enviarMensaje($telefono,
                "No pude procesar tu audio. ¿Puedes escribirme tu consulta? 😊"
            );
            return null;
        }

        $transcripcion = $whisper->transcribir($rutaTemp, $extension);

        if (!$transcripcion) {
            $this->enviarMensaje($telefono,
                "No entendí el audio. ¿Puedes escribirme tu consulta? 😊"
            );
            return null;
        }

        Log::info("[WhatsApp] Audio transcrito de {$telefono}: {$transcripcion}");

        // Indicar al bot que viene de un audio
        return "[Audio transcrito]: {$transcripcion}";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // IMAGEN — descarga + Claude Vision
    // ─────────────────────────────────────────────────────────────────────────

    private function procesarImagen(array $msg, string $telefono): ?string
    {
        $mediaId  = $msg['image']['id']      ?? null;
        $caption  = $msg['image']['caption'] ?? '';

        if (!$mediaId) {
            return null;
        }

        // Descargar imagen
        $rutaTemp = $this->descargarMedia($mediaId, 'jpg');

        if (!$rutaTemp) {
            $this->enviarMensaje($telefono,
                "No pude procesar la imagen. ¿Puedes describirme lo que necesitas? 😊"
            );
            return null;
        }

        // Si el cliente tiene una venta pendiente de pago, tratamos esta imagen
        // como comprobante de transferencia: se guarda el archivo real, se extraen
        // datos de referencia (monto, fecha, hora, N° operación) con Claude Vision,
        // y queda en revision manual del staff (backoffice > verificacion de pagos).
        // El bot NUNCA confirma la reserva ni aprueba montos/fechas por su cuenta —
        // los datos extraidos son solo lectura automatica de referencia.
        $ventaPendiente = $this->buscarVentaPendiente($telefono);
        if ($ventaPendiente) {
            $resultado = $this->guardarComprobante($rutaTemp, $ventaPendiente);
            if (file_exists($rutaTemp)) {
                @unlink($rutaTemp);
            }
            if ($resultado['ok']) {
                Log::info('[WhatsApp] Comprobante guardado para revision', [
                    'venta_id'   => $ventaPendiente->venta_id,
                    'reserva_id' => $ventaPendiente->reserva_id,
                    'extraido'   => $resultado,
                ]);

                $detalle = 'Datos detectados en la imagen (lectura automática, puede tener errores): '
                    . 'monto=' . ($resultado['monto'] !== null ? '$' . number_format($resultado['monto'], 0, ',', '.') : 'no legible')
                    . ', fecha=' . ($resultado['fecha'] ?? 'no legible')
                    . ', hora=' . ($resultado['hora'] ?? 'no legible')
                    . ', N° operación=' . ($resultado['numero_operacion'] ?? 'no legible')
                    . ', origen=' . ($resultado['nombre_origen'] ?? 'no legible') . '.';

                if (!empty($resultado['alertas'])) {
                    $detalle .= ' Posibles diferencias a revisar: ' . implode('; ', $resultado['alertas']) . '.';
                }

                return "[Sistema-comprobante: Se recibió y guardó la foto del comprobante de "
                    . "transferencia para la reserva N°{$ventaPendiente->reserva_id}. {$detalle} "
                    . "Quedó en revisión manual del equipo de Botacura — estos datos son SOLO una "
                    . "lectura automática de referencia, el sistema NO aprueba ni rechaza el pago. "
                    . "Responde al cliente confirmando amablemente lo que se detectó (para que pueda "
                    . "corregirte si algo está mal leído) y explica que el equipo lo revisará y "
                    . "confirmará el pago a la brevedad. NUNCA digas que la reserva quedó "
                    . "\"confirmada\" o \"asegurada\", ni apruebes ni niegues montos o diferencias tú "
                    . "mismo — eso lo define el equipo, no tú.]";
            }
        }

        // Describir con Claude Vision (caso general: no hay venta pendiente de pago)
        $descripcion = $this->describirConClaude($rutaTemp, $caption);

        if (file_exists($rutaTemp)) {
            @unlink($rutaTemp);
        }

        if (!$descripcion) {
            // Si Claude Vision falla, al menos pasar el caption
            $texto = $caption ?: "[El cliente envió una imagen]";
            return $texto;
        }

        Log::info("[WhatsApp] Imagen descrita de {$telefono}: " . substr($descripcion, 0, 100));

        $texto = "[Imagen recibida]: {$descripcion}";
        if ($caption) {
            $texto .= " | Caption del cliente: \"{$caption}\"";
        }

        return $texto;
    }

    private function buscarVentaPendiente(string $telefono)
    {
        $telefonoNorm = $this->normalizarTelefono($telefono);

        return DB::table('ventas')
            ->join('reservas', 'reservas.id', '=', 'ventas.id_reserva')
            ->join('clientes', 'clientes.id', '=', 'reservas.cliente_id')
            ->where('clientes.whatsapp_cliente', $telefonoNorm)
            ->where('ventas.estado_pago', 'pendiente')
            ->orderBy('ventas.id', 'desc')
            ->select('ventas.id as venta_id', 'reservas.id as reserva_id', 'ventas.abono_programa', 'ventas.diferencia_programa')
            ->first();
    }

    private function guardarComprobante(string $rutaTemp, $ventaPendiente): array
    {
        $ventaId = $ventaPendiente->venta_id;
        try {
            $nombreArchivo = 'venta_' . $ventaId . '_' . now()->format('Ymd_His') . '_' . uniqid() . '.jpg';
            Storage::disk('comprobante_transferencia')->put($nombreArchivo, file_get_contents($rutaTemp));

            $datos = $this->extraerDatosComprobante($rutaTemp) ?? [];

            $monto           = isset($datos['monto']) && is_numeric($datos['monto']) ? (int) $datos['monto'] : null;
            $fecha           = (!empty($datos['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha'])) ? $datos['fecha'] : null;
            $hora            = !empty($datos['hora']) ? substr($datos['hora'], 0, 10) : null;
            $numeroOperacion = !empty($datos['numero_operacion']) ? substr($datos['numero_operacion'], 0, 100) : null;
            $nombreOrigen    = !empty($datos['nombre_origen']) ? substr($datos['nombre_origen'], 0, 200) : null;

            $abono = (int) ($ventaPendiente->abono_programa ?? 0);
            $total = $abono + (int) ($ventaPendiente->diferencia_programa ?? 0);

            $tipoDetectado = 'no_detectado';
            $alertas = [];

            if ($monto !== null) {
                if ($total > 0 && $monto >= $total * 0.97) {
                    $tipoDetectado = 'total';
                } elseif ($abono > 0 && $monto >= $abono * 0.97) {
                    $tipoDetectado = 'abono_50';
                } else {
                    $tipoDetectado = 'monto_insuficiente';
                    $alertas[] = "monto detectado (\${$monto}) no coincide con el abono (\${$abono}) ni el total (\${$total})";
                }
            } else {
                $alertas[] = 'no se pudo leer el monto en la imagen';
            }

            if ($fecha !== null) {
                $dias = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($fecha)->startOfDay());
                if ($dias > 1) {
                    $alertas[] = "la fecha de la transferencia ({$fecha}) no coincide con hoy";
                }
            } else {
                $alertas[] = 'no se pudo leer la fecha en la imagen';
            }

            DB::table('ventas')->where('id', $ventaId)->update([
                'comprobante_transferencia'    => $nombreArchivo,
                'comprobante_monto'            => $monto,
                'comprobante_fecha'            => $fecha,
                'comprobante_hora'             => $hora,
                'comprobante_numero_operacion' => $numeroOperacion,
                'comprobante_nombre_origen'    => $nombreOrigen,
                'comprobante_tipo_detectado'   => $tipoDetectado,
                'comprobante_alerta'           => $alertas ? implode(' | ', $alertas) : null,
                'estado_pago'                  => 'pendiente_verificacion',
                'updated_at'                   => now(),
            ]);

            return [
                'ok'               => true,
                'monto'            => $monto,
                'fecha'            => $fecha,
                'hora'             => $hora,
                'numero_operacion' => $numeroOperacion,
                'nombre_origen'    => $nombreOrigen,
                'tipo_detectado'   => $tipoDetectado,
                'alertas'          => $alertas,
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Error guardando comprobante: ' . $e->getMessage(), ['venta_id' => $ventaId]);
            return ['ok' => false];
        }
    }

    private function extraerDatosComprobante(string $rutaLocal): ?array
    {
        $apiKey = config('services.anthropic.key', '');
        $model  = config('services.anthropic.model', 'claude-haiku-4-5-20251001');

        if (!$apiKey || !file_exists($rutaLocal)) {
            return null;
        }

        try {
            $imageData = base64_encode(file_get_contents($rutaLocal));
            $mimeType  = 'image/jpeg';

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $rutaLocal);
                finfo_close($finfo);
                if ($detected && strpos($detected, 'image/') === 0) {
                    $mimeType = $detected;
                }
            }

            $prompt = "Esta imagen es (probablemente) un comprobante de transferencia bancaria chilena. "
                . "Extrae SOLO estos datos y responde ÚNICAMENTE con un objeto JSON, sin texto antes ni "
                . "después, sin backticks:\n"
                . "{\n"
                . "  \"es_comprobante\": true o false,\n"
                . "  \"monto\": numero entero en pesos chilenos sin puntos ni simbolo, o null,\n"
                . "  \"fecha\": \"YYYY-MM-DD\" o null,\n"
                . "  \"hora\": \"HH:MM\" o null,\n"
                . "  \"numero_operacion\": el numero/folio/ID de la transaccion tal como aparece "
                . "(cualquier formato, con o sin simbolos) o null,\n"
                . "  \"nombre_origen\": nombre de quien envia la plata, o null,\n"
                . "  \"banco_o_app_origen\": banco o app usada, o null\n"
                . "}\n"
                . "No inventes datos. Si algo no se lee con certeza, usa null en ese campo.";

            $client   = new GuzzleClient(['timeout' => 20, 'http_errors' => false]);
            $response = $client->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => $model,
                    'max_tokens' => 400,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type'   => 'image',
                                    'source' => [
                                        'type'       => 'base64',
                                        'media_type' => $mimeType,
                                        'data'       => $imageData,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 300) {
                Log::error('[Vision] Claude error extrayendo comprobante ' . $response->getStatusCode());
                return null;
            }

            $body    = json_decode((string) $response->getBody(), true) ?? [];
            $content = trim($body['content'][0]['text'] ?? '');
            $content = preg_replace('/^```json\s*/i', '', $content);
            $content = preg_replace('/```\s*$/', '', $content);
            $content = trim($content);

            $firstBrace = strpos($content, '{');
            $lastBrace  = strrpos($content, '}');
            $jsonCandidate = ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace)
                ? substr($content, $firstBrace, $lastBrace - $firstBrace + 1)
                : $content;

            $parsed = json_decode($jsonCandidate, true);
            return is_array($parsed) ? $parsed : null;

        } catch (\Exception $e) {
            Log::error('[Vision] Error extrayendo datos comprobante: ' . $e->getMessage());
            return null;
        }
    }

    private function normalizarTelefono(string $telefono)
    {
        $limpio = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($limpio) === 9 && substr($limpio, 0, 1) === '9') {
            $limpio = '56' . $limpio;
        }
        return $limpio;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CLAUDE VISION — describe una imagen
    // ─────────────────────────────────────────────────────────────────────────

    private function describirConClaude(string $rutaLocal, string $contexto = ''): ?string
    {
        $apiKey = config('services.anthropic.key', '');
        $model  = config('services.anthropic.model', 'claude-haiku-4-5-20251001');

        if (!$apiKey || !file_exists($rutaLocal)) {
            return null;
        }

        try {
            $imageData  = base64_encode(file_get_contents($rutaLocal));
            $mimeType   = 'image/jpeg';

            // Detectar tipo real por extensión o magic bytes
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = finfo_file($finfo, $rutaLocal);
                finfo_close($finfo);
                if ($detected && strpos($detected, 'image/') === 0) {
                    $mimeType = $detected;
                }
            }

            $promptVision = "Describe brevemente esta imagen en español. "
                . "Si es una consulta sobre servicios, precios, instalaciones o reservas de un spa/centro recreativo llamado Botacura, "
                . "indícalo. Si contiene texto legible, transcríbelo. "
                . "Sé conciso (máx 100 palabras).";

            if ($contexto) {
                $promptVision .= " El cliente adjuntó este texto: \"{$contexto}\"";
            }

            $client   = new GuzzleClient(['timeout' => 20, 'http_errors' => false]);
            $response = $client->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => $model,
                    'max_tokens' => 300,
                    'messages'   => [
                        [
                            'role'    => 'user',
                            'content' => [
                                [
                                    'type'  => 'image',
                                    'source' => [
                                        'type'       => 'base64',
                                        'media_type' => $mimeType,
                                        'data'       => $imageData,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $promptVision,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() >= 300) {
                Log::error('[Vision] Claude error ' . $response->getStatusCode());
                return null;
            }

            $body = json_decode((string) $response->getBody(), true) ?? [];
            return trim($body['content'][0]['text'] ?? '');

        } catch (\Exception $e) {
            Log::error('[Vision] Error: ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS — Meta Cloud API media download
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Descarga un archivo de media de Meta y lo guarda en un temporal.
     * Retorna la ruta local o null si falló.
     */
    private function descargarMedia(string $mediaId, string $extension): ?string
    {
        $token   = env('META_WHATSAPP_TOKEN');
        $version = env('META_API_VERSION', 'v19.0');

        try {
            $client = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);

            // 1. Obtener URL del media
            $metaRes = $client->get("https://graph.facebook.com/{$version}/{$mediaId}", [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]);

            if ($metaRes->getStatusCode() !== 200) {
                Log::error('[WhatsApp] No se pudo obtener URL media', ['id' => $mediaId, 'status' => $metaRes->getStatusCode()]);
                return null;
            }

            $metaData = json_decode((string) $metaRes->getBody(), true) ?? [];
            $mediaUrl = $metaData['url'] ?? null;

            if (!$mediaUrl) {
                Log::error('[WhatsApp] URL de media vacía', ['id' => $mediaId]);
                return null;
            }

            // 2. Descargar el archivo
            $rutaTemp = tempnam(sys_get_temp_dir(), 'wa_media_') . '.' . $extension;

            $dlRes = $client->get($mediaUrl, [
                'headers' => ['Authorization' => 'Bearer ' . $token],
                'sink'    => $rutaTemp,
            ]);

            if ($dlRes->getStatusCode() !== 200 || !file_exists($rutaTemp) || filesize($rutaTemp) === 0) {
                Log::error('[WhatsApp] Descarga de media fallida', ['id' => $mediaId]);
                return null;
            }

            Log::info('[WhatsApp] Media descargado', ['id' => $mediaId, 'bytes' => filesize($rutaTemp)]);
            return $rutaTemp;

        } catch (\Exception $e) {
            Log::error('[WhatsApp] Error descargando media: ' . $e->getMessage());
            return null;
        }
    }

    private function mimeToExtension(string $mime): string
    {
        $map = [
            'audio/ogg'         => 'ogg',
            'audio/mpeg'        => 'mp3',
            'audio/mp4'         => 'm4a',
            'audio/webm'        => 'webm',
            'audio/wav'         => 'wav',
            'audio/x-m4a'       => 'm4a',
            'audio/amr'         => 'amr',
        ];
        // El mime puede venir con codecs: "audio/ogg; codecs=opus"
        $base = explode(';', $mime)[0];
        return $map[trim($base)] ?? 'ogg';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS — envío de mensajes
    // ─────────────────────────────────────────────────────────────────────────

    private function enviarMensaje(string $telefono, string $texto)
    {
        $phoneId = $this->phoneNumberId ?: env('META_PHONE_NUMBER_ID');
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
                Log::error('[WhatsApp] Error enviando mensaje', [
                    'status' => $res->getStatusCode(),
                    'body'   => (string) $res->getBody(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Excepción enviando mensaje: ' . $e->getMessage());
        }
    }

    private function enviarDocumento(string $telefono, string $url, string $nombre)
    {
        $phoneId = $this->phoneNumberId ?: env('META_PHONE_NUMBER_ID');
        $token   = env('META_WHATSAPP_TOKEN');
        $version = env('META_API_VERSION', 'v19.0');

        try {
            $client = new GuzzleClient(['timeout' => 10, 'http_errors' => false]);
            $client->post("https://graph.facebook.com/{$version}/{$phoneId}/messages", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'messaging_product' => 'whatsapp',
                    'to'                => $telefono,
                    'type'              => 'document',
                    'document'          => ['link' => $url, 'filename' => $nombre],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('[WhatsApp] Excepción enviando documento: ' . $e->getMessage());
        }
    }
}
