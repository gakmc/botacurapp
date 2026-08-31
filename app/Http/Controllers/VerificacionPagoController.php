<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * VerificacionPagoController
 *
 * Verificacion visual manual de comprobantes de transferencia enviados por
 * clientes via WhatsApp (bot). El bot deja la venta en estado_pago
 * "pendiente_verificacion" con la imagen guardada; el staff aprueba o
 * rechaza aqui, lo que confirma (o no) la reserva y notifica al cliente.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class VerificacionPagoController extends Controller
{
    public function index()
    {
        $pendientes = DB::table('ventas as v')
            ->join('reservas as r', 'r.id', '=', 'v.id_reserva')
            ->leftJoin('clientes as c', 'c.id', '=', 'r.cliente_id')
            ->leftJoin('programas as p', 'p.id', '=', 'r.id_programa')
            ->where('v.estado_pago', 'pendiente_verificacion')
            ->select(
                'v.id as venta_id', 'v.total_pagar', 'v.abono_programa',
                'v.comprobante_transferencia', 'v.created_at',
                'r.id as reserva_id', 'r.fecha_visita',
                'c.nombre_cliente as cliente_nombre', 'c.whatsapp_cliente as numero_contacto',
                'p.nombre_programa'
            )
            ->orderBy('v.created_at', 'asc')
            ->get();

        return view('themes.backoffice.pages.verificacion_pago.index', compact('pendientes'));
    }

    /**
     * GET /backoffice/verificacion-pago/{venta}/imagen
     * Sirve la imagen del comprobante desde el disco privado.
     */
    public function imagen($ventaId)
    {
        $venta = DB::table('ventas')->where('id', $ventaId)->first();
        abort_if(!$venta || !$venta->comprobante_transferencia, 404);

        $path = $venta->comprobante_transferencia;
        abort_if(!Storage::disk('comprobante_transferencia')->exists($path), 404);

        return Storage::disk('comprobante_transferencia')->response($path);
    }

    public function aprobar($ventaId)
    {
        $venta = DB::table('ventas')->where('id', $ventaId)->first();
        abort_if(!$venta, 404);

        DB::table('ventas')->where('id', $ventaId)->update([
            'estado_pago'    => 'pagado',
            'verificado_por' => auth()->id(),
            'verificado_at'  => now(),
            'updated_at'     => now(),
        ]);

        DB::table('reservas')->where('id', $venta->id_reserva)->update([
            'estado'     => 'confirmada',
            'updated_at' => now(),
        ]);

        Log::info('[VerificacionPago] Comprobante aprobado', ['venta_id' => $ventaId, 'por' => auth()->id()]);

        $this->notificarCliente($venta->id_reserva, true);

        return redirect()->route('backoffice.verificacion-pago.index')
            ->with('success', 'Comprobante aprobado. Reserva confirmada y cliente notificado.');
    }

    public function rechazar($ventaId)
    {
        $venta = DB::table('ventas')->where('id', $ventaId)->first();
        abort_if(!$venta, 404);

        DB::table('ventas')->where('id', $ventaId)->update([
            'estado_pago'    => 'rechazado',
            'verificado_por' => auth()->id(),
            'verificado_at'  => now(),
            'updated_at'     => now(),
        ]);

        Log::info('[VerificacionPago] Comprobante rechazado', ['venta_id' => $ventaId, 'por' => auth()->id()]);

        $this->notificarCliente($venta->id_reserva, false);

        return redirect()->route('backoffice.verificacion-pago.index')
            ->with('success', 'Comprobante rechazado. Se notifico al cliente para que reenvie el comprobante.');
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function notificarCliente(int $reservaId, bool $aprobado)
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

            $nombre = $cliente->nombre_cliente ?? 'Cliente';

            $mensaje = $aprobado
                ? "¡Hola {$nombre}! 🎉 Verificamos tu comprobante de transferencia.\n\n"
                  . "✅ *Reserva N°{$reservaId} confirmada*\n\n"
                  . "Nos contactaremos contigo los días previos a tu visita para coordinar los horarios del spa. ¡Te esperamos! 🏔️"
                : "Hola {$nombre}, no pudimos verificar el comprobante que enviaste para la reserva N°{$reservaId}. "
                  . "Por favor envía nuevamente una foto clara del comprobante de transferencia, o contáctanos al +56 9 7448 4112.";

            $this->enviarMensajeWhatsApp($cliente->whatsapp_cliente, $mensaje);

            if ($aprobado) {
                // Pago confirmado: ahora si corresponde pedir la seleccion de menu,
                // adjuntando el PDF real del menu de otono.
                $mensajeMenu = "🌿 Te compartimos las opciones de menú para que puedas elegir.\n"
                    . "Un día antes de tu reserva te enviaremos los horarios disponibles de spa, siempre que ya tengamos tus opciones de menú confirmadas.\n"
                    . "Si no recibimos la selección a tiempo, se asignará el horario disponible sin posibilidad de modificación.\n"
                    . "Quedamos atentas 🙏";
                $this->enviarMensajeWhatsApp($cliente->whatsapp_cliente, $mensajeMenu);
                $this->enviarDocumentoWhatsApp($cliente->whatsapp_cliente, url('/docs/menu-otono-2026.pdf'), 'Menu-Otono-2026.pdf');
            }

        } catch (\Exception $e) {
            Log::error('[VerificacionPago] Error notificando: ' . $e->getMessage());
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
                Log::error('[VerificacionPago] Error enviando WhatsApp', [
                    'status' => $res->getStatusCode(),
                    'body'   => (string) $res->getBody(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[VerificacionPago] Excepción enviando WhatsApp: ' . $e->getMessage());
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
                Log::error('[VerificacionPago] Error enviando documento WhatsApp', [
                    'status' => $res->getStatusCode(),
                    'body'   => (string) $res->getBody(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[VerificacionPago] Excepción enviando documento WhatsApp: ' . $e->getMessage());
        }
    }
}
