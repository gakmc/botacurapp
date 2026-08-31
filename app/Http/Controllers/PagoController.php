<?php

namespace App\Http\Controllers;

use App\Services\WebpayService;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PagoController
 *
 * Maneja el retorno de Transbank Webpay Plus tras el pago.
 *
 * Flujo:
 * 1. Cliente paga en Webpay
 * 2. Transbank redirige a GET /pago/webpay/retorno?token_ws={token}
 * 3. Confirmamos la transacción con PUT al token
 * 4. Actualizamos estado_pago en ventas
 * 5. Notificamos al cliente por WhatsApp
 * 6. Mostramos vista de resultado
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class PagoController extends Controller
{
    /**
     * GET /pago/webpay/retorno
     *
     * Transbank redirige aquí tras el pago (aprobado, rechazado o anulado).
     * También puede venir TBK_TOKEN si el usuario abandonó el pago.
     */
    public function retornoWebpay(Request $request)
    {
        $tokenWs  = $request->input('token_ws');
        $tbkToken = $request->input('TBK_TOKEN'); // usuario presionó "Anular" en Webpay

        // ── Pago anulado / abandonado ──────────────────────────────────────────
        if (!$tokenWs && $tbkToken) {
            Log::info('[Pago] Usuario anuló pago en Webpay', ['TBK_TOKEN' => substr($tbkToken, 0, 10)]);
            $venta = DB::table('ventas')->where('webpay_token', $tbkToken)->first();
            if ($venta) {
                DB::table('ventas')->where('id', $venta->id)->update([
                    'estado_pago' => 'anulado',
                    'updated_at'  => now(),
                ]);
            }
            return view('pago.resultado', [
                'exito'   => false,
                'mensaje' => 'Pago anulado. Si deseas reintentar, contacta a Botacura al +56 9 7448 4112.',
            ]);
        }

        if (!$tokenWs) {
            Log::warning('[Pago] Retorno sin token_ws ni TBK_TOKEN');
            return view('pago.resultado', [
                'exito'   => false,
                'mensaje' => 'No se recibió información del pago. Contacta a Botacura al +56 9 7448 4112.',
            ]);
        }

        // ── Confirmar con Transbank ────────────────────────────────────────────
        $webpay       = new WebpayService();
        $confirmacion = $webpay->confirmarTransaccion($tokenWs);

        if (!$confirmacion) {
            Log::error('[Pago] No se pudo confirmar con Transbank', ['token' => substr($tokenWs, 0, 10)]);
            return view('pago.resultado', [
                'exito'   => false,
                'mensaje' => 'Error al confirmar el pago. Por favor contacta a Botacura al +56 9 7448 4112.',
            ]);
        }

        $aprobado = $webpay->esAprobado($confirmacion);

        // ── Buscar la venta por token ─────────────────────────────────────────
        $venta = DB::table('ventas')->where('webpay_token', $tokenWs)->first();

        if ($venta) {
            $estadoPago = $aprobado ? 'pagado' : 'rechazado';

            DB::table('ventas')->where('id', $venta->id)->update([
                'estado_pago' => $estadoPago,
                'folio_abono' => $confirmacion['authorization_code'] ?? null,
                'updated_at'  => now(),
            ]);

            Log::info('[Pago] Venta actualizada', [
                'venta_id'    => $venta->id,
                'estado_pago' => $estadoPago,
                'monto'       => $confirmacion['amount'] ?? 0,
            ]);

            if ($aprobado) {
                // Actualizar estado de reserva
                DB::table('reservas')->where('id', $venta->id_reserva)->update([
                    'estado'     => 'confirmada',
                    'updated_at' => now(),
                ]);

                // Notificar por WhatsApp
                $this->notificarPagoConfirmado($venta->id_reserva, $confirmacion);
            }
        } else {
            Log::warning('[Pago] No se encontró venta para token', ['token' => substr($tokenWs, 0, 10)]);
        }

        return view('pago.resultado', [
            'exito'    => $aprobado,
            'mensaje'  => $aprobado
                ? '¡Pago recibido! Tu reserva está confirmada. Recibirás un mensaje de WhatsApp con los detalles. 🎉'
                : 'El pago fue rechazado. Por favor intenta nuevamente o contacta a Botacura al +56 9 7448 4112.',
            'monto'    => $confirmacion['amount'] ?? null,
            'orden'    => $confirmacion['buy_order'] ?? null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function notificarPagoConfirmado(int $reservaId, array $confirmacion)
    {
        try {
            $reserva = DB::table('reservas')->where('id', $reservaId)->first();
            if (!$reserva) {
                return;
            }

            $cliente = DB::table('clientes')->where('id', $reserva->cliente_id)->first();
            if (!$cliente || empty($cliente->whatsapp_cliente)) {
                return;
            }

            $programa = DB::table('programas')->where('id', $reserva->id_programa)->first();

            $telefono   = $cliente->whatsapp_cliente;
            $nombre     = $cliente->nombre_cliente ?? 'Cliente';
            $nombreProg = $programa ? $programa->nombre_programa : 'tu programa';
            $fecha      = $reserva->fecha_visita ?? '';
            $monto      = number_format($confirmacion['amount'] ?? 0, 0, ',', '.');

            $mensaje = "¡Hola {$nombre}! 🎉 Recibimos tu pago de \${$monto}.\n\n"
                . "✅ *Reserva N°{$reservaId} confirmada*\n"
                . "📅 {$fecha}\n"
                . "🌿 {$nombreProg}\n\n"
                . "Nos contactaremos contigo los días previos a tu visita para coordinar los horarios del spa. ¡Te esperamos! 🏔️";

            $this->enviarMensajeWhatsApp($telefono, $mensaje);

            // Pago confirmado: ahora sí corresponde pedir la selección de menú,
            // adjuntando el PDF real del menú de otoño.
            $mensajeMenu = "🌿 Te compartimos las opciones de menú para que puedas elegir.\n"
                . "Un día antes de tu reserva te enviaremos los horarios disponibles de spa, siempre que ya tengamos tus opciones de menú confirmadas.\n"
                . "Si no recibimos la selección a tiempo, se asignará el horario disponible sin posibilidad de modificación.\n"
                . "Quedamos atentas 🙏";
            $this->enviarMensajeWhatsApp($telefono, $mensajeMenu);
            $this->enviarDocumentoWhatsApp($telefono, url('/docs/menu-otono-2026.pdf'), 'Menu-Otono-2026.pdf');

        } catch (\Exception $e) {
            Log::error('[Pago] Error notificando pago: ' . $e->getMessage());
        }
    }

    private function enviarMensajeWhatsApp(string $telefono, string $texto)
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
                Log::error('[Pago] Error enviando WhatsApp post-pago', [
                    'status' => $res->getStatusCode(),
                    'body'   => (string) $res->getBody(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[Pago] Excepción enviando WhatsApp: ' . $e->getMessage());
        }
    }

    private function enviarDocumentoWhatsApp(string $telefono, string $url, string $nombre)
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
                    'type'              => 'document',
                    'document'          => ['link' => $url, 'filename' => $nombre],
                ],
            ]);

            if ($res->getStatusCode() >= 300) {
                Log::error('[Pago] Error enviando documento WhatsApp post-pago', [
                    'status' => $res->getStatusCode(),
                    'body'   => (string) $res->getBody(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[Pago] Excepción enviando documento WhatsApp: ' . $e->getMessage());
        }
    }
}
