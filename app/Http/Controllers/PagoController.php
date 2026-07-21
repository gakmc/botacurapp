<?php

namespace App\Http\Controllers;

use App\Services\FintocService;
use App\Services\MetaWhatsAppService;
use App\Services\ReservaPdfService;
use App\Services\WebpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PagoController — Página pública de pago para reservas del bot WhatsApp
 *
 * Rutas (web.php):
 *   GET  /pago/{reserva_id}            → opciones()  : página de pago
 *   POST /pago/{reserva_id}/webpay     → webpayInit(): inicia transacción Webpay
 *   GET|POST /pago/webpay/retorno      → webpayReturn(): Transbank llama aquí tras el pago
 *
 * Rutas (api.php):
 *   POST /api/fintoc/webhook           → fintocWebhook(): Fintoc notifica transferencias
 */
class PagoController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // PÁGINA DE OPCIONES DE PAGO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /pago/{reserva_id}
     * Página pública con las opciones de pago (transferencia + Webpay).
     */
    public function opciones(Request $request, int $reservaId)
    {
        $reserva = DB::table('reservas as r')
            ->join('clientes as c', 'r.cliente_id', '=', 'c.id')
            ->join('programas as p', 'r.id_programa', '=', 'p.id')
            ->leftJoin('ventas as v', 'v.id_reserva', '=', 'r.id')
            ->where('r.id', $reservaId)
            ->select(
                'r.id', 'r.fecha_visita', 'r.cantidad_personas', 'r.estado',
                'c.nombre_cliente',
                'p.nombre_programa',
                'v.id as venta_id', 'v.total_pagar', 'v.monto_pagado',
                'v.estado_pago', 'v.referencia_transferencia'
            )
            ->first();

        if (!$reserva) {
            abort(404, 'Reserva no encontrada.');
        }

        // Si ya está pagada, mostrar confirmación en vez de opciones
        if (in_array($reserva->estado_pago, ['pago_completo', 'abono_recibido'])) {
            return view('pago.confirmado', compact('reserva'));
        }

        $abono50    = (int) ceil($reserva->total_pagar / 2);
        $cuentaBank = [
            'banco'    => 'BancoEstado',
            'tipo'     => 'Cuenta Corriente',
            'numero'   => env('BANCO_NUMERO_CUENTA', 'XXXX-XXXX-XXXX'),
            'rut'      => env('BANCO_RUT', 'XX.XXX.XXX-X'),
            'nombre'   => env('BANCO_NOMBRE', 'Botacura SpA'),
            'email'    => env('BANCO_EMAIL', 'hola@botacura.cl'),
        ];

        return view('pago.opciones', compact('reserva', 'abono50', 'cuentaBank'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // WEBPAY PLUS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /pago/{reserva_id}/webpay
     * Inicia una transacción Webpay Plus y redirige al usuario a Transbank.
     */
    public function webpayInit(Request $request, int $reservaId)
    {
        $venta = DB::table('ventas')->where('id_reserva', $reservaId)->first();

        if (!$venta) {
            return back()->withErrors(['No se encontró información de pago para esta reserva.']);
        }

        $monto     = (int) $venta->total_pagar;
        $buyOrder  = 'BTC-' . $reservaId . '-' . now()->format('His'); // máx 26 chars
        $sessionId = 'rsv-' . $reservaId;
        $returnUrl = route('pago.webpay.retorno');

        $webpay = new WebpayService();
        $result = $webpay->initTransaction($monto, $buyOrder, $sessionId, $returnUrl);

        if (!$result['ok']) {
            Log::error('[PagoController] Webpay initTransaction falló', [
                'reserva_id' => $reservaId,
                'error'      => $result['error'],
            ]);
            return back()->withErrors(['Error al iniciar el pago. Por favor intenta de nuevo.']);
        }

        // Guardar token en ventas para poder hacer commit después
        DB::table('ventas')->where('id', $venta->id)->update([
            'webpay_token' => $result['token'],
            'webpay_orden' => $buyOrder,
            'metodo_pago'  => 'webpay',
            'estado_pago'  => 'iniciado',
            'updated_at'   => now(),
        ]);

        // Redirigir a Transbank: POST al url con token_ws
        // La forma estándar es un formulario auto-submit
        $webpayUrl   = $result['url'];
        $webpayToken = $result['token'];

        return view('pago.webpay_redirect', compact('webpayUrl', 'webpayToken'));
    }

    /**
     * GET|POST /pago/webpay/retorno
     * Transbank redirige aquí con token_ws (pago OK) o TBK_TOKEN (pago abortado).
     */
    public function webpayReturn(Request $request)
    {
        $tokenWs  = $request->input('token_ws');
        $tbkToken = $request->input('TBK_TOKEN');

        // Pago abortado por el usuario
        if ($tbkToken && !$tokenWs) {
            Log::info('[PagoController] Webpay abortado por el usuario', ['TBK_TOKEN' => $tbkToken]);
            $venta = DB::table('ventas')->where('webpay_token', $tbkToken)->first();
            if ($venta) {
                DB::table('ventas')->where('id', $venta->id)->update([
                    'estado_pago' => 'abortado',
                    'updated_at'  => now(),
                ]);
                return redirect()->route('pago.opciones', ['reserva_id' => $venta->id_reserva])
                    ->with('warning', 'El pago fue cancelado. Puedes intentarlo de nuevo.');
            }
            return view('pago.resultado', ['exito' => false, 'mensaje' => 'Pago cancelado.', 'reserva' => null]);
        }

        if (!$tokenWs) {
            return view('pago.resultado', ['exito' => false, 'mensaje' => 'Token de pago inválido.', 'reserva' => null]);
        }

        // Commit la transacción
        $webpay = new WebpayService();
        $result = $webpay->commitTransaction($tokenWs);

        // Buscar la venta por token
        $venta = DB::table('ventas')->where('webpay_token', $tokenWs)->first();

        if (!$result['ok'] || !$result['aprobado']) {
            Log::error('[PagoController] Webpay commit fallido', [
                'token'  => $tokenWs,
                'result' => $result,
            ]);
            if ($venta) {
                DB::table('ventas')->where('id', $venta->id)->update([
                    'estado_pago' => 'fallido',
                    'updated_at'  => now(),
                ]);
            }
            return view('pago.resultado', [
                'exito'   => false,
                'mensaje' => 'El pago no pudo ser procesado. Intenta de nuevo o contáctanos.',
                'reserva' => $venta ? DB::table('reservas')->find($venta->id_reserva) : null,
            ]);
        }

        // Pago aprobado
        $data  = $result['data'];
        $monto = (int) ($data['amount'] ?? 0);

        $reserva = null;

        // ── Flujo bot: reserva aún no existe, crearla ahora ──────────────────
        $pendiente = DB::table('webpay_pendientes')->where('webpay_token', $tokenWs)->first();

        if ($pendiente) {
            $d          = json_decode($pendiente->datos_json, true);
            $botUserId  = $d['bot_user_id'] ?? 1;
            $programaId = (int) $d['programa_id'];
            $personas   = (int) $d['personas'];
            $clienteId  = (int) $d['cliente_id'];

            DB::transaction(function () use (
                $d, $monto, $tokenWs, $pendiente, $botUserId, $programaId,
                $personas, $clienteId, &$reserva
            ) {
                // 1. Reserva
                $reservaId = DB::table('reservas')->insertGetId([
                    'cliente_id'             => $clienteId,
                    'cantidad_personas'      => $personas,
                    'cantidad_masajes'       => $d['incluye_masaje'] ? $personas : null,
                    'cantidad_masajes_extra' => $d['masajes_extra'] ?? 0,
                    'fecha_visita'           => $d['fecha'],
                    'observacion'            => 'Reserva Bot WhatsApp',
                    'id_programa'            => $programaId,
                    'user_id'                => $botUserId,
                    'estado'                 => 'pagado',
                    'fuente'                 => 'bot_whatsapp',
                    'menu_recibido'          => 0,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                // 2. Venta con pago confirmado
                $ventaId = DB::table('ventas')->insertGetId([
                    'id_reserva'      => $reservaId,
                    'abono_programa'  => $monto,
                    'total_pagar'     => $pendiente->monto,
                    'monto_pagado'    => $monto,
                    'estado_pago'     => 'pago_completo',
                    'metodo_pago'     => 'webpay',
                    'webpay_token'    => $tokenWs,
                    'webpay_orden'    => $pendiente->webpay_orden,
                    'confirmado_en'   => now(),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // 3. Visita placeholder
                DB::table('visitas')->insert([
                    'id_reserva' => $reservaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 4. Masajes incluidos
                if ($d['incluye_masaje']) {
                    for ($i = 1; $i <= $personas; $i++) {
                        DB::table('masajes')->insert([
                            'id_reserva' => $reservaId,
                            'persona'    => $i,
                            'user_id'    => $botUserId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // 5. Masajes extra
                $base = $d['incluye_masaje'] ? $personas : 0;
                for ($i = 1; $i <= ($d['masajes_extra'] ?? 0); $i++) {
                    DB::table('masajes')->insert([
                        'id_reserva' => $reservaId,
                        'persona'    => $base + $i,
                        'user_id'    => $botUserId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // 6. Menús incluidos
                if ($d['incluye_menu']) {
                    for ($i = 0; $i < $personas; $i++) {
                        DB::table('menus')->insert([
                            'id_reserva'    => $reservaId,
                            'tipo_servicio' => $d['tipo_servicio'] ?? null,
                            'created_at'    => now(),
                            'updated_at'    => now(),
                        ]);
                    }
                }

                // 7. Menús extra
                for ($i = 0; $i < ($d['menus_extra'] ?? 0); $i++) {
                    DB::table('menus')->insert([
                        'id_reserva'    => $reservaId,
                        'tipo_servicio' => $d['tipo_servicio'] ?? null,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }

                // 8. Eliminar pendiente
                DB::table('webpay_pendientes')->where('id', $pendiente->id)->delete();

                // Cargar datos para la vista y notificaciones
                $reserva = DB::table('reservas as r')
                    ->join('clientes as c', 'r.cliente_id', '=', 'c.id')
                    ->join('programas as p', 'r.id_programa', '=', 'p.id')
                    ->where('r.id', $reservaId)
                    ->select('r.*', 'c.nombre_cliente', 'c.whatsapp_cliente', 'p.nombre_programa', 'p.id as programa_id')
                    ->first();
            });

            Log::info('[PagoController] Reserva bot creada post-pago', [
                'reserva_id' => $reserva->id ?? null,
                'monto'      => $monto,
            ]);

        } elseif ($venta) {
            // ── Flujo manual: reserva ya existía (página /pago/{id}) ─────────
            DB::table('ventas')->where('id', $venta->id)->update([
                'monto_pagado'   => $monto,
                'abono_programa' => $monto,
                'estado_pago'    => 'pago_completo',
                'confirmado_en'  => now(),
                'updated_at'     => now(),
            ]);
            DB::table('reservas')->where('id', $venta->id_reserva)->update([
                'estado'     => 'pagado',
                'updated_at' => now(),
            ]);

            $reserva = DB::table('reservas as r')
                ->join('clientes as c', 'r.cliente_id', '=', 'c.id')
                ->join('programas as p', 'r.id_programa', '=', 'p.id')
                ->where('r.id', $venta->id_reserva)
                ->select('r.*', 'c.nombre_cliente', 'c.whatsapp_cliente', 'p.nombre_programa', 'p.id as programa_id')
                ->first();
        }

        Log::info('[PagoController] Webpay pago aprobado', [
            'token'      => $tokenWs,
            'monto'      => $monto,
            'auth_code'  => $data['authorization_code'] ?? null,
            'reserva_id' => $reserva->id ?? null,
        ]);

        // Notificar al cliente por WhatsApp (async-safe: errores no afectan la vista)
        if ($reserva && $reserva->whatsapp_cliente) {
            try {
                $this->notificarPagoWhatsApp($reserva, $monto);
            } catch (\Throwable $e) {
                Log::error('[PagoController] Error notificando WhatsApp post-pago', [
                    'reserva_id' => $reserva->id,
                    'error'      => $e->getMessage(),
                ]);
            }
        }

        return view('pago.resultado', [
            'exito'     => true,
            'mensaje'   => '¡Pago realizado con éxito!',
            'reserva'   => $reserva,
            'monto'     => $monto,
            'auth_code' => $data['authorization_code'] ?? null,
            'card'      => $data['card_detail']['card_number'] ?? null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NOTIFICACIÓN WHATSAPP POST-PAGO
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Envía al cliente por WhatsApp:
     *  1. Mensaje de confirmación de pago
     *  2. PDF de la reserva
     *  3. Pregunta desayuno/once (si programa lo incluye) o oferta de add-on
     */
    private function notificarPagoWhatsApp($reserva, int $monto): void
    {
        $wa        = new MetaWhatsAppService();
        $telefono  = $reserva->whatsapp_cliente;
        $nombre    = explode(' ', $reserva->nombre_cliente)[0]; // primer nombre
        $montoFmt  = '$' . number_format($monto, 0, ',', '.');
        $fecha     = \Carbon\Carbon::parse($reserva->fecha_visita)->locale('es')->isoFormat('dddd D [de] MMMM');

        // ── 1. Mensaje de confirmación ────────────────────────────────────────
        $wa->sendTextMessage($telefono,
            "✅ ¡{$nombre}, tu pago de {$montoFmt} fue recibido!\n\n" .
            "📋 *Reserva N°{$reserva->id}* confirmada para el *{$fecha}*.\n" .
            "Te llegará tu confirmación ahora mismo 👇"
        );

        // ── 2. PDF de reserva ─────────────────────────────────────────────────
        $pdfContent = ReservaPdfService::generarPdf($reserva->id);
        if ($pdfContent) {
            $mediaId = $wa->uploadMedia($pdfContent, 'application/pdf', "reserva_{$reserva->id}.pdf");
            if ($mediaId) {
                $wa->sendDocument(
                    $telefono,
                    $mediaId,
                    "Confirmacion_Reserva_{$reserva->id}.pdf",
                    "📄 Confirmación de tu reserva N°{$reserva->id} en Botacura Cajón del Maipo 🏔️"
                );
            }
        }

        // ── 3. Desayuno / Once ────────────────────────────────────────────────
        $servicios = DB::table('servicios as s')
            ->join('programa_servicio as ps', 'ps.servicio_id', '=', 's.id')
            ->where('ps.programa_id', $reserva->programa_id)
            ->pluck('s.nombre_servicio')
            ->toArray();

        $tieneDesayunoUOnce  = in_array('Desayuno u Once',  $servicios);
        $tieneDesayunoYOnce  = in_array('Desayuno y Once',  $servicios);
        $tieneAlguno         = $tieneDesayunoUOnce || $tieneDesayunoYOnce;

        // Verificar si ya tiene tipo_servicio asignado en los menús
        $tipoYaAsignado = DB::table('menus')
            ->where('id_reserva', $reserva->id)
            ->whereNotNull('tipo_servicio')
            ->exists();

        if ($tieneDesayunoYOnce) {
            // Programa con ambos — solo confirmar, no preguntar
            $wa->sendTextMessage($telefono,
                "☕🫖 Tu plan incluye *Desayuno (10:30)* y *Once (17:00)*. ¡Solo preséntate y a disfrutar! 🌿"
            );
        } elseif ($tieneDesayunoUOnce && !$tipoYaAsignado) {
            // Programa incluye uno de los dos — preguntar cuál prefieren
            $wa->sendTextMessage($telefono,
                "🍽️ Tu plan incluye un servicio de alimentación extra. ¿Prefieren:\n\n" .
                "☕ *Desayuno* — 10:30 a 12:00 hrs\n" .
                "🫖 *Once* — 17:00 a 18:15 hrs\n\n" .
                "Responde con *Desayuno* u *Once* y lo dejamos coordinado 🙌"
            );
        } elseif (!$tieneAlguno) {
            // Programa sin alimentación extra — ofrecer como add-on
            $wa->sendTextMessage($telefono,
                "🍽️ ¿Sabías que puedes agregar *Desayuno u Once buffet* a tu visita?\n\n" .
                "☕ *Desayuno* — 10:30 a 12:00 hrs · $10.000/persona\n" .
                "🫖 *Once* — 17:00 a 18:15 hrs · $10.000/persona\n\n" .
                "Si te interesa, responde con *Desayuno* o *Once* y coordino con el equipo 🙏"
            );
        }

        // ── Menú del almuerzo ──────────────────────────────────────────────────
        $this->enviarMenuAlmuerzo($wa, $telefono, $reserva->id, $nombre);
    }

    /**
     * Envía las opciones de menú por WhatsApp para que el cliente elija.
     */
    private function enviarMenuAlmuerzo(MetaWhatsAppService $wa, string $telefono, int $reservaId, string $nombre): void
    {
        try {
            $entradas = DB::table('productos as p')
                ->join('tipos_productos as t', 't.id', '=', 'p.id_tipo_producto')
                ->where('p.estado', 'activo')
                ->where('t.nombre', 'entrada')
                ->orderBy('p.nombre')
                ->pluck('p.nombre')
                ->toArray();

            $fondos = DB::table('productos as p')
                ->join('tipos_productos as t', 't.id', '=', 'p.id_tipo_producto')
                ->where('p.estado', 'activo')
                ->where('t.nombre', 'fondo')
                ->orderBy('p.nombre')
                ->pluck('p.nombre')
                ->toArray();

            $acompañamientos = DB::table('productos as p')
                ->join('tipos_productos as t', 't.id', '=', 'p.id_tipo_producto')
                ->where('p.estado', 'activo')
                ->where('t.nombre', 'acompañamiento')
                ->orderBy('p.nombre')
                ->pluck('p.nombre')
                ->toArray();

            $listaEntradas = '';
            foreach ($entradas as $i => $e) {
                $listaEntradas .= ($i + 1) . ". {$e}\n";
            }
            $listaFondos = '';
            foreach ($fondos as $i => $f) {
                $listaFondos .= ($i + 1) . ". {$f}\n";
            }
            $listaAcomp = '';
            foreach ($acompañamientos as $i => $a) {
                $listaAcomp .= ($i + 1) . ". {$a}\n";
            }
            if ($listaAcomp) {
                $listaAcomp .= "0. Sin acompañamiento\n";
            }

            if (!$listaEntradas || !$listaFondos) {
                Log::warning("[PagoController] No se encontraron opciones de menú para enviar por WhatsApp");
                return;
            }

            $mensaje = "🍽️ ¡Ahora elige el *menú del almuerzo* para tu visita!\n\n"
                . "*ENTRADAS:*\n{$listaEntradas}\n"
                . "*FONDOS:*\n{$listaFondos}"
                . ($listaAcomp ? "\n*ACOMPAÑAMIENTOS:*\n{$listaAcomp}" : '')
                . "\nResponde indicando tu elección de *entrada y fondo* "
                . "(y acompañamiento si deseas). "
                . "Si todos van a comer lo mismo, dímelo una sola vez. "
                . "Si cada persona elige distinto, indícame por persona 🙌";

            $wa->sendTextMessage($telefono, $mensaje);

            Log::info("[PagoController] Opciones de menú enviadas a {$telefono} para reserva {$reservaId}");

        } catch (\Exception $e) {
            Log::error("[PagoController] Error enviando menú WhatsApp: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FINTOC WEBHOOK (API)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/fintoc/webhook
     * Fintoc notifica cuando llega una transferencia a la cuenta BancoEstado.
     */
    public function fintocWebhook(Request $request)
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('X-Fintoc-Signature', '');

        if (!FintocService::validateWebhookSignature($rawBody, $signature)) {
            Log::warning('[Fintoc] Firma de webhook inválida');
            return response()->json(['ok' => false, 'error' => 'Firma inválida'], 401);
        }

        $payload = json_decode($rawBody, true);
        if (!$payload) {
            return response()->json(['ok' => false, 'error' => 'JSON inválido'], 400);
        }

        $result = FintocService::handleWebhook($payload);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
