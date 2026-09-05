<?php

namespace App\Http\Controllers\Api;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Programa;
use App\Reserva;
use App\Services\BotPromptService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Illuminate\Support\Facades\Log;

/**
 * BotController
 *
 * Endpoints del chatbot Bot-Acura (WhatsApp + Instagram)
 * Compatible Laravel 6 / PHP 7.2
 *
 * Rutas (prefijo /api/bot, middleware bot.token):
 *   GET  ping
 *   GET  programas
 *   GET  disponibilidad?fecha=YYYY-MM-DD
 *   POST clientes/buscar-o-crear
 *   POST reservas
 *   POST reservas/{id}/pago
 *   GET  conversacion/{usuario_id}
 *   POST conversacion
 *   POST message           ← nuevo: endpoint Claude para n8n
 */
class BotController extends Controller
{
    const BOT_SECRET_HEADER = 'X-Bot-Secret';
    const MAX_HISTORY_TURNS = 12;

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/ping
    // ─────────────────────────────────────────────────────────────
    public function ping()
    {
        return response()->json([
            'status'    => 'ok',
            'mensaje'   => 'Bot-Acura API activa',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/programas
    // ─────────────────────────────────────────────────────────────
    public function programas()
    {
        $rows = DB::table('programas as p')
            ->where('p.estado', 'activo')
            ->leftJoin('programa_servicio as ps', 'ps.id_programa', '=', 'p.id')
            ->leftJoin('servicios as s', 's.id', '=', 'ps.id_servicio')
            ->select('p.id', 'p.nombre_programa', 'p.slug', 'p.valor_programa', 'p.descuento', 's.nombre_servicio', 's.duracion')
            ->orderBy('p.valor_programa')
            ->get();

        $agrupados = [];
        foreach ($rows as $row) {
            $id = $row->id;
            if (!isset($agrupados[$id])) {
                $descuento  = (int) ($row->descuento ?? 0);
                $valor      = (int) $row->valor_programa;
                $valorFinal = $valor - $descuento;
                $agrupados[$id] = [
                    'id'               => $id,
                    'nombre'           => $row->nombre_programa,
                    'slug'             => $row->slug,
                    'valor'            => $valor,
                    'descuento'        => $descuento,
                    'valor_final'      => $valorFinal,
                    'valor_formateado' => '$' . number_format($valorFinal, 0, ',', '.'),
                    'servicios'        => [],
                ];
            }
            if ($row->nombre_servicio) {
                $agrupados[$id]['servicios'][] = [
                    'nombre'   => $row->nombre_servicio,
                    'duracion' => $row->duracion,
                ];
            }
        }

        return response()->json(['programas' => array_values($agrupados)]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/disponibilidad?fecha=YYYY-MM-DD[&programa_id=N&personas=N]
    // ─────────────────────────────────────────────────────────────
    public function disponibilidad(Request $request)
    {
        $fecha = $request->query('fecha');

        if (!$fecha || !$this->esFechaValida($fecha)) {
            return response()->json(['error' => 'Fecha inválida. Formato: YYYY-MM-DD'], 422);
        }

        $fechaCarbon = Carbon::parse($fecha);
        $diaSemana   = $fechaCarbon->dayOfWeek; // 0=Dom, 4=Jue, 5=Vie, 6=Sáb
        $esOperativo = in_array($diaSemana, [0, 4, 5, 6]);

        if (!$esOperativo) {
            return response()->json([
                'disponible'        => false,
                'fecha'             => $fecha,
                'dia'               => $fechaCarbon->locale('es')->isoFormat('dddd'),
                'mensaje'           => 'Botacura opera jueves a domingo y festivos.',
                'cupos_disponibles' => 0,
                'reservas_actuales' => 0,
            ]);
        }

        if ($fechaCarbon->isPast() && !$fechaCarbon->isToday()) {
            return response()->json([
                'disponible'        => false,
                'fecha'             => $fecha,
                'mensaje'           => 'La fecha ya pasó.',
                'cupos_disponibles' => 0,
                'reservas_actuales' => 0,
            ]);
        }

        // Verificar que el dia este habilitado por el staff (calendario admin,
        // tabla fecha_disponibles). Antes de este fix el bot ignoraba por
        // completo los dias marcados como no habilitados (mantencion, feriados
        // internos, etc) y podia seguir tomando reservas para esas fechas.
        $habilitada = \App\FechaDisponible::where('fecha', $fecha)->where('habilitada', true)->exists();
        if (!$habilitada) {
            return response()->json([
                'disponible'        => false,
                'fecha'             => $fecha,
                'mensaje'           => 'Ese día no está habilitado para reservas.',
                'cupos_disponibles' => 0,
                'reservas_actuales' => 0,
            ]);
        }

        // Si viene programa_id, usar la lógica de DisponibilidadController
        if ($request->filled('programa_id')) {
            return app(DisponibilidadController::class)->check($request);
        }

        // Sin programa_id: contar reservas totales del día
        $cuposMax       = 16; // slots de tinaja
        $reservasActual = Reserva::whereDate('fecha_visita', $fecha)
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'cancelada');
            })
            ->count();
        $cuposDisp      = max(0, $cuposMax - $reservasActual);

        return response()->json([
            'disponible'        => $cuposDisp > 0,
            'fecha'             => $fecha,
            'dia'               => $fechaCarbon->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
            'cupos_maximos'     => $cuposMax,
            'reservas_actuales' => $reservasActual,
            'cupos_disponibles' => $cuposDisp,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/clientes/buscar-o-crear
    // Body: { nombre_cliente, whatsapp_cliente, canal, instagram_cliente? }
    // ─────────────────────────────────────────────────────────────
    public function buscarOCrearCliente(Request $request)
    {
        $telefono = $this->normalizarTelefono($request->input('whatsapp_cliente', ''));
        $canal    = $request->input('canal', 'whatsapp');

        if (empty($telefono) && $canal === 'whatsapp') {
            return response()->json(['error' => 'whatsapp_cliente es requerido'], 422);
        }

        $esNuevo = false;
        $cliente = null;

        if ($telefono) {
            $cliente = Cliente::where('whatsapp_cliente', $telefono)->first();
        }
        if (!$cliente && $canal === 'instagram') {
            $igId = $request->input('instagram_cliente');
            if ($igId) {
                $cliente = Cliente::where('instagram_cliente', $igId)->first();
            }
        }

        if (!$cliente) {
            $nombre = $request->input('nombre_cliente', 'Cliente Bot');
            try {
                $cliente = Cliente::create([
                    'nombre_cliente'    => $nombre,
                    'whatsapp_cliente'  => $telefono ?: null,
                    'instagram_cliente' => $request->input('instagram_cliente', null),
                    'sexo'              => null,
                    'correo'            => $this->generarCorreoTemporal($telefono, $canal),
                ]);
                $esNuevo = true;
                Log::info("[Bot] Cliente creado #{$cliente->id} vía {$canal}");
            } catch (\Exception $e) {
                Log::error("[Bot] Error creando cliente: " . $e->getMessage());
                return response()->json(['error' => 'No se pudo crear el cliente'], 500);
            }
        } else {
            $actualizaciones = [];
            if (empty($cliente->whatsapp_cliente) && $telefono) {
                $actualizaciones['whatsapp_cliente'] = $telefono;
            }
            if (!empty($actualizaciones)) {
                $cliente->update($actualizaciones);
            }
            Log::info("[Bot] Cliente encontrado #{$cliente->id}");
        }

        return response()->json([
            'cliente' => [
                'id'               => $cliente->id,
                'nombre_cliente'   => $cliente->nombre_cliente,
                'whatsapp_cliente' => $cliente->whatsapp_cliente,
                'correo'           => $cliente->correo,
            ],
            'es_nuevo' => $esNuevo,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/reservas
    // Body: { cliente_id, id_programa, fecha_visita, cantidad_personas, canal? }
    // ─────────────────────────────────────────────────────────────
    public function crearReserva(Request $request)
    {
        $request->validate([
            'cliente_id'        => 'required|integer|exists:clientes,id',
            'id_programa'       => 'required|integer|exists:programas,id',
            'fecha_visita'      => 'required|date|after_or_equal:today',
            'cantidad_personas' => 'required|integer|min:1|max:50',
        ]);

        // Validar disponibilidad real antes de insertar (evita doble-reserva).
        // Usa la misma logica que GET /api/bot-ai/disponibilidad?programa_id=...
        $chequeoRequest = new Request([
            'fecha'       => $request->fecha_visita,
            'programa_id' => $request->id_programa,
            'personas'    => $request->cantidad_personas,
        ]);
        $chequeoResponse = app(DisponibilidadController::class)->check($chequeoRequest);
        $chequeo = json_decode($chequeoResponse->getContent(), true);

        if (!($chequeo['disponible'] ?? false)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'sin_cupo',
                'mensaje' => $chequeo['motivo_no_disponible'] ?? 'No hay cupo disponible para esa fecha.',
                'detalle' => $chequeo,
            ], 409);
        }

        $botUserId = (int) env('BOT_SYSTEM_USER_ID', 1);

        $reservaId = DB::table('reservas')->insertGetId([
            'cliente_id'        => $request->cliente_id,
            'id_programa'       => $request->id_programa,
            'fecha_visita'      => $request->fecha_visita,
            'cantidad_personas' => $request->cantidad_personas,
            'cantidad_masajes'  => 0,
            'observacion'       => 'Creada por bot WhatsApp',
            'user_id'           => $botUserId,
            'estado'            => 'pendiente_pago',
            'fuente'            => 'bot_whatsapp',
            'menu_recibido'     => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $programa   = DB::table('programas')->where('id', $request->id_programa)->first();
        $valorTotal = ($programa ? (int) $programa->valor_programa : 0) * (int) $request->cantidad_personas;
        $abono50    = (int) ceil($valorTotal / 2);

        Log::info("[Bot] Reserva creada #{$reservaId}");

        return response()->json([
            'ok'                 => true,
            'reserva_id'         => $reservaId,
            'valor_total'        => $valorTotal,
            'valor_total_fmt'    => '$' . number_format($valorTotal, 0, ',', '.'),
            'abono_50'           => $abono50,
            'abono_50_fmt'       => '$' . number_format($abono50, 0, ',', '.'),
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/bot-ai/reservas/{id}/cancelar
    // Body: { motivo? }
    // Nota: el abono NO es reembolsable - solo se marca la reserva como
    // cancelada, cualquier devolucion se maneja manualmente por el staff.
    // -----------------------------------------------------------------------
    public function cancelarReserva(Request $request, $id)
    {
        $reserva = DB::table('reservas')->where('id', $id)->first();
        if (!$reserva) {
            return response()->json(['ok' => false, 'error' => 'Reserva no encontrada'], 404);
        }

        if ($reserva->estado === 'cancelada') {
            return response()->json([
                'ok'         => true,
                'reserva_id' => (int) $id,
                'estado'     => 'cancelada',
                'mensaje'    => 'La reserva ya estaba cancelada.',
            ]);
        }

        $motivo = $request->input('motivo');
        $observacionNueva = trim(($reserva->observacion ? $reserva->observacion . ' | ' : '')
            . 'Cancelada por bot WhatsApp' . ($motivo ? ": {$motivo}" : ''));

        DB::table('reservas')->where('id', $id)->update([
            'estado'      => 'cancelada',
            'observacion' => $observacionNueva,
            'updated_at'  => now(),
        ]);

        Log::info("[Bot] Reserva #{$id} cancelada" . ($motivo ? " - motivo: {$motivo}" : ''));

        return response()->json([
            'ok'         => true,
            'reserva_id' => (int) $id,
            'estado'     => 'cancelada',
            'mensaje'    => 'Reserva cancelada. El abono no es reembolsable.',
        ]);
    }

    // -----------------------------------------------------------------------
    // POST /api/bot-ai/reservas/{id}/reprogramar
    // Body: { nueva_fecha: YYYY-MM-DD }
    // Politicas: minimo 3 dias corridos de anticipacion antes de las 10:00,
    // una sola reprogramacion por reserva, nueva fecha dentro de 45 dias
    // desde la fecha original, y el programa debe admitir cambios.
    // -----------------------------------------------------------------------
    public function reprogramarReserva(Request $request, $id)
    {
        $request->validate([
            'nueva_fecha' => 'required|date|after_or_equal:today',
        ]);

        $reserva = DB::table('reservas')->where('id', $id)->first();
        if (!$reserva) {
            return response()->json(['ok' => false, 'error' => 'Reserva no encontrada'], 404);
        }

        if ($reserva->estado === 'cancelada') {
            return response()->json(['ok' => false, 'error' => 'La reserva esta cancelada, no se puede reprogramar'], 422);
        }

        $nuevaFecha = $request->nueva_fecha;

        if ($nuevaFecha === $reserva->fecha_visita) {
            return response()->json(['ok' => false, 'error' => 'La nueva fecha es igual a la actual'], 422);
        }

        $yaReprogramada = DB::table('reagendamientos')->where('id_reserva', $id)->exists();
        if ($yaReprogramada) {
            return response()->json([
                'ok'      => false,
                'error'   => 'limite_reprogramacion',
                'mensaje' => 'Esta reserva ya fue reprogramada una vez. No se permite una segunda reprogramacion.',
            ], 422);
        }

        $programa = DB::table('programas')->where('id', $reserva->id_programa)->first();
        if ($programa && isset($programa->permite_reprogramacion) && !$programa->permite_reprogramacion) {
            return response()->json([
                'ok'      => false,
                'error'   => 'programa_no_modificable',
                'mensaje' => 'Este programa no admite cambios de fecha segun las politicas de Botacura.',
            ], 422);
        }

        $fechaVisitaOriginal = Carbon::parse($reserva->fecha_visita, 'America/Santiago');
        $limitePlazo = $fechaVisitaOriginal->copy()->subDays(3)->setTime(10, 0, 0);
        $ahora = Carbon::now('America/Santiago');

        if ($ahora->greaterThanOrEqualTo($limitePlazo)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'fuera_de_plazo',
                'mensaje' => 'La solicitud esta fuera del plazo permitido (minimo 3 dias corridos de anticipacion, antes de las 10:00). No corresponde reprogramacion segun las politicas de Botacura.',
            ], 422);
        }

        $limiteVentana = $fechaVisitaOriginal->copy()->addDays(45);
        $nuevaFechaCarbon = Carbon::parse($nuevaFecha, 'America/Santiago');
        if ($nuevaFechaCarbon->greaterThan($limiteVentana)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'fuera_de_ventana',
                'mensaje' => 'La nueva fecha debe estar dentro de los 45 dias corridos siguientes a la fecha original de la reserva.',
            ], 422);
        }

        $chequeoRequest = new Request([
            'fecha'       => $nuevaFecha,
            'programa_id' => $reserva->id_programa,
            'personas'    => $reserva->cantidad_personas,
        ]);
        $chequeoResponse = app(DisponibilidadController::class)->check($chequeoRequest);
        $chequeo = json_decode($chequeoResponse->getContent(), true);

        if (!($chequeo['disponible'] ?? false)) {
            return response()->json([
                'ok'      => false,
                'error'   => 'sin_cupo',
                'mensaje' => $chequeo['motivo_no_disponible'] ?? 'No hay cupo disponible para la nueva fecha.',
                'detalle' => $chequeo,
            ], 409);
        }

        $fechaAnterior = $reserva->fecha_visita;

        DB::table('reagendamientos')->insert([
            'fecha_original' => $fechaAnterior,
            'nueva_fecha'    => $nuevaFecha,
            'id_reserva'     => $id,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        DB::table('reservas')->where('id', $id)->update([
            'fecha_visita' => $nuevaFecha,
            'updated_at'   => now(),
        ]);

        Log::info("[Bot] Reserva #{$id} reprogramada de {$fechaAnterior} a {$nuevaFecha}");

        return response()->json([
            'ok'             => true,
            'reserva_id'     => (int) $id,
            'fecha_anterior' => $fechaAnterior,
            'fecha_nueva'    => $nuevaFecha,
            'mensaje'        => 'Reserva reprogramada correctamente.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/reservas/{id}/pago
    // Body: { monto, comprobante_url? }
    // ─────────────────────────────────────────────────────────────
    public function registrarPago(Request $request, $id)
    {
        $reserva = DB::table('reservas')->where('id', $id)->first();
        if (!$reserva) {
            return response()->json(['error' => 'Reserva no encontrada'], 404);
        }

        DB::table('reservas')->where('id', $id)->update([
            'estado'     => 'pago_parcial',
            'updated_at' => now(),
        ]);

        Log::info("[Bot] Pago registrado para reserva #{$id}");

        return response()->json([
            'ok'         => true,
            'reserva_id' => $id,
            'estado'     => 'pago_parcial',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/conversacion/{usuario_id}
    // ─────────────────────────────────────────────────────────────
    public function getConversacion($usuarioId)
    {
        $conv = DB::table('bot_conversaciones')
            ->where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->orderBy('id', 'desc')
            ->first();

        if (!$conv) {
            return response()->json(['conversacion' => null]);
        }

        return response()->json(['conversacion' => $conv]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/conversacion
    // Body: { usuario_id, canal?, paso?, nombre_cliente?, ... }
    // ─────────────────────────────────────────────────────────────
    public function upsertConversacion(Request $request)
    {
        $usuarioId = $request->input('usuario_id');
        if (!$usuarioId) {
            return response()->json(['error' => 'usuario_id requerido'], 422);
        }

        $conv = DB::table('bot_conversaciones')
            ->where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->first();

        $campos = array_filter([
            'canal'          => $request->input('canal'),
            'paso'           => $request->input('paso'),
            'nombre_cliente' => $request->input('nombre_cliente'),
            'telefono'       => $request->input('telefono'),
            'correo'         => $request->input('correo'),
            'id_programa'    => $request->input('id_programa'),
            'cantidad_personas' => $request->input('cantidad_personas'),
            'fecha_visita'   => $request->input('fecha_visita'),
            'politicas_aceptadas' => $request->input('politicas_aceptadas'),
            'ultimo_mensaje' => $request->input('ultimo_mensaje'),
            'historial_json' => $request->input('historial_json'),
            'activo'         => $request->input('activo'),
            'motivo_cierre'  => $request->input('motivo_cierre'),
        ], function ($v) { return $v !== null; });

        $campos['updated_at'] = now();

        if ($conv) {
            DB::table('bot_conversaciones')->where('id', $conv->id)->update($campos);
            $id = $conv->id;
        } else {
            $campos['usuario_id'] = $usuarioId;
            $campos['canal']      = $campos['canal'] ?? 'whatsapp';
            $campos['activo']     = 1;
            $campos['paso']       = 0;
            $campos['created_at'] = now();
            $id = DB::table('bot_conversaciones')->insertGetId($campos);
        }

        return response()->json([
            'ok'              => true,
            'conversacion_id' => $id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/message
    // Endpoint principal para n8n — Claude maneja la conversación completa
    // Body: { telefono, mensaje, nombre? }
    // ─────────────────────────────────────────────────────────────
    public function message(Request $request)
    {
        // Auth
        $secret = config('services.bot.secret');
        if ($secret && $request->header(self::BOT_SECRET_HEADER) !== $secret) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'telefono' => 'required|string',
            'mensaje'  => 'required|string|max:4000',
            'nombre'   => 'nullable|string|max:100',
        ]);

        $usuarioId = $this->normalizarTelefono($request->telefono);
        $mensaje   = trim($request->mensaje);
        $nombre    = $request->nombre ?? 'Cliente';

        // Cargar/crear conversación
        $conv = $this->obtenerConversacion($usuarioId, $nombre);

        // Historial para Claude
        $historial = json_decode($conv->historial_json ?? '[]', true) ?: [];

        $contenidoParaHistorial = $mensaje;

        // Si es el primer mensaje de una conversación nueva, revisar si el número
        // ya es cliente conocido (evita re-pedir nombre/correo a quien ya reservó antes).
        if (empty($historial)) {
            $clienteConocido = DB::table('clientes')->where('whatsapp_cliente', $usuarioId)->first();
            if ($clienteConocido) {
                $ctxCliente = "[Sistema: Este número ya es cliente de Botacura — nombre: "
                    . "{$clienteConocido->nombre_cliente}"
                    . (!empty($clienteConocido->correo) ? ", correo: {$clienteConocido->correo}" : '')
                    . ". Salúdalo como cliente que vuelve, usando su nombre. Ya tienes su nombre"
                    . (!empty($clienteConocido->correo) ? ' y correo' : '')
                    . " — no se los vuelvas a pedir desde cero, solo confírmaselos brevemente "
                    . "salvo que él mismo diga que cambiaron. El resto del proceso de reserva "
                    . "(fecha, programa, personas, políticas, pago) sigue igual — cada visita es "
                    . "una reserva nueva.]";
                $contenidoParaHistorial = $mensaje . "\n\n" . $ctxCliente;
            }
        }

        $historial[] = ['role' => 'user', 'content' => $contenidoParaHistorial];

        // System prompt con programas y menú dinámicos desde BD
        $programas    = $this->cargarProgramasBd();
        $menuOpciones = $this->cargarMenuOpciones();
        $promptSvc    = new BotPromptService();
        $systemPrompt = $promptSvc->getSystemPrompt($programas, $menuOpciones);

        // Llamar a Claude
        $respuesta = $this->llamarClaude($systemPrompt, $historial, $nombre);

        if (!$respuesta) {
            $msgError = 'Estamos teniendo problemas técnicos. Por favor escríbenos al +56 9 7448 4112 🙏';
            $historial[] = ['role' => 'assistant', 'content' => $msgError];
            $this->persistirHistorial($conv->id, $mensaje, $msgError, $historial, []);
            return response()->json([
                'ok'     => true,
                'accion' => 'escalar_humano',
                'mensaje' => $msgError,
                'datos'  => ['motivo' => 'error_claude_api'],
            ]);
        }

        $accion = $respuesta['accion'] ?? 'responder';
        $datos  = $respuesta['datos']  ?? [];

        // Procesar acciones especiales
        if ($accion === 'verificar_disponibilidad') {
            $respuesta = $this->procesarDisponibilidad($respuesta, $systemPrompt, $historial, $mensaje, $nombre);
        } elseif ($accion === 'crear_reserva') {
            $respuesta = $this->procesarCrearReservaClaude($respuesta, $systemPrompt, $historial, $mensaje, $nombre, $usuarioId);
        } elseif ($accion === 'guardar_seleccion_menu') {
            $respuesta = $this->procesarGuardarSeleccionMenu($respuesta, $systemPrompt, $historial, $mensaje, $nombre);
        }

        $historial[] = ['role' => 'assistant', 'content' => $respuesta['mensaje'] ?? ''];

        if (count($historial) > self::MAX_HISTORY_TURNS * 2) {
            $historial = array_slice($historial, -(self::MAX_HISTORY_TURNS * 2));
        }

        $this->persistirHistorial($conv->id, $mensaje, $respuesta['mensaje'] ?? '', $historial, $respuesta['datos'] ?? []);

        return response()->json(array_merge(['ok' => true], $respuesta));
    }

    // ─────────────────────────────────────────────────────────────
    // INTERNOS — Claude
    // ─────────────────────────────────────────────────────────────

    private function llamarClaude(string $systemPrompt, array $historial, string $nombre, int $intento = 1)
    {
        $apiKey = config('services.anthropic.key');
        $model  = config('services.anthropic.model', 'claude-haiku-4-5-20251001');

        try {
            $client   = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);
            $response = $client->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => $model,
                    'max_tokens' => 1024,
                    'system'     => $systemPrompt . "\n\nNombre del cliente: {$nombre}",
                    'messages'   => $historial,
                ],
            ]);

            if ($response->getStatusCode() >= 300) {
                Log::error('[Bot] Claude error', ['status' => $response->getStatusCode()]);
                return null;
            }

            $body    = json_decode((string) $response->getBody(), true) ?? [];
            $content = $body['content'][0]['text'] ?? '';
            $content = trim($content);
            $content = preg_replace('/^```json\s*/i', '', $content);
            $content = preg_replace('/```\s*$/', '', $content);
            $content = trim($content);

            // Extraer SOLO el bloque JSON aunque el modelo agregue texto antes/despues
            // (evita que texto o backticks sueltos se filtren al cliente como mensaje).
            $firstBrace = strpos($content, '{');
            $lastBrace  = strrpos($content, '}');
            $jsonCandidate = ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace)
                ? substr($content, $firstBrace, $lastBrace - $firstBrace + 1)
                : $content;

            $parsed = json_decode($jsonCandidate, true);

            if (!$parsed || !isset($parsed['accion'], $parsed['mensaje'])) {
                Log::warning('[Bot] No se pudo parsear respuesta de Claude como JSON (intento ' . $intento . ')', ['raw' => substr($content, 0, 500)]);

                // Reintenta UNA vez pidiendole explicitamente formato JSON valido, antes
                // de mostrarle al cliente un mensaje generico de error.
                if ($intento < 2) {
                    $historialReintento   = $historial;
                    $historialReintento[] = ['role' => 'assistant', 'content' => $content];
                    $historialReintento[] = [
                        'role'    => 'user',
                        'content' => '[Sistema: tu respuesta anterior no fue un JSON válido. Responde ÚNICAMENTE con el objeto JSON {"accion":..., "mensaje":..., "datos":...} tal como se especifica en tus instrucciones, sin texto antes ni después, sin backticks ni explicaciones adicionales.]',
                    ];
                    return $this->llamarClaude($systemPrompt, $historialReintento, $nombre, $intento + 1);
                }

                return [
                    'accion'  => 'responder',
                    'mensaje' => 'Disculpa, tuve un problema procesando tu mensaje 🙏 ¿Puedes repetirlo?',
                    'datos'   => [],
                ];
            }
            return $parsed;

        } catch (\Exception $e) {
            Log::error('[Bot] Excepción Claude: ' . $e->getMessage());
            return null;
        }
    }

    private function procesarGuardarSeleccionMenu(array $respuesta, string $systemPrompt, array $historial, string $msgUsuario, string $nombre)
    {
        $datos = $respuesta['datos'] ?? [];
        if (empty($datos['reserva_id'])) {
            return $respuesta;
        }
        try {
            $secret   = config('services.bot.secret');
            $botToken = env('BOT_API_TOKEN');
            $client = new GuzzleClient(['timeout' => 10, 'http_errors' => false]);
            $res    = $client->patch(url('/api/bot-ai/reserva/' . (int) $datos['reserva_id'] . '/menu-seleccion'), [
                'headers' => [
                    self::BOT_SECRET_HEADER => $secret,
                    'X-Bot-Token'           => $botToken,
                    'content-type'          => 'application/json',
                ],
                'json' => ['selecciones' => $datos['selecciones'] ?? []],
            ]);
            $ctx = '[Sistema-menu: ' . json_encode(json_decode((string) $res->getBody(), true), JSON_UNESCAPED_UNICODE) . ']';
            $historial[] = ['role' => 'user', 'content' => $msgUsuario . "\n\n" . $ctx];
            return $this->llamarClaude($systemPrompt, $historial, $nombre) ?: $respuesta;
        } catch (\Exception $e) {
            Log::error('[Bot] Error guardando selección menú: ' . $e->getMessage());
            return $respuesta;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot-ai/menu-opciones
    // Retorna productos activos agrupados por tipo (entrada/fondo/acompañamiento)
    // ─────────────────────────────────────────────────────────────
    public function menuOpciones()
    {
        $opciones = $this->cargarMenuOpciones();
        return response()->json(['ok' => true, 'menu' => $opciones]);
    }

    private function procesarDisponibilidad(array $respuesta, string $systemPrompt, array $historial, string $msgUsuario, string $nombre)
    {
        $datos = $respuesta['datos'] ?? [];
        if (empty($datos['fecha'])) {
            return $respuesta;
        }
        try {
            $secret   = config('services.bot.secret');
            $botToken = env('BOT_API_TOKEN');
            $client = new GuzzleClient(['timeout' => 10, 'http_errors' => false]);
            $query = [
                'fecha'    => $datos['fecha'],
                'personas' => $datos['personas'] ?? 1,
            ];
            if (!empty($datos['programa_id'])) {
                $query['programa_id'] = $datos['programa_id'];
            }
            $disp   = $client->get(url('/api/bot-ai/disponibilidad'), [
                'headers' => [
                    self::BOT_SECRET_HEADER => $secret,
                    'X-Bot-Token'           => $botToken,
                ],
                'query' => $query,
            ]);
            $ctx = '[Sistema-disponibilidad: ' . json_encode(json_decode((string) $disp->getBody(), true), JSON_UNESCAPED_UNICODE) . ']';
            $historial[] = ['role' => 'user', 'content' => $msgUsuario . "\n\n" . $ctx];
            return $this->llamarClaude($systemPrompt, $historial, $nombre) ?: $respuesta;
        } catch (\Exception $e) {
            Log::error('[Bot] Error disponibilidad: ' . $e->getMessage());
            return $respuesta;
        }
    }

    private function procesarCrearReservaClaude(array $respuesta, string $systemPrompt, array $historial, string $msgUsuario, string $nombre, string $usuarioId)
    {
        $datos = $respuesta['datos'] ?? [];
        foreach (['nombre', 'email', 'programa_id', 'fecha', 'personas'] as $campo) {
            if (empty($datos[$campo])) {
                return $respuesta;
            }
        }
        try {
            $secret   = config('services.bot.secret');
            $botToken = env('BOT_API_TOKEN');
            $client = new GuzzleClient(['timeout' => 15, 'http_errors' => false]);
            $res    = $client->post(url('/api/bot-ai/reserva'), [
                'headers' => [
                    self::BOT_SECRET_HEADER => $secret,
                    'X-Bot-Token'           => $botToken,
                    'content-type'          => 'application/json',
                ],
                'json' => [
                    'nombre'         => $datos['nombre'],
                    'telefono'       => !empty($datos['telefono']) ? $datos['telefono'] : $usuarioId,
                    'email'          => $datos['email'],
                    'programa_id'    => $datos['programa_id'],
                    'fecha'          => $datos['fecha'],
                    'personas'       => $datos['personas'],
                    'masajes_extra'  => $datos['masajes_extra']  ?? 0,
                    'desayuno_once'  => $datos['desayuno_once']  ?? 0,
                    'desayuno_tipo'  => $datos['desayuno_tipo']  ?? null,
                    'observacion'    => $datos['observacion']    ?? null,
                    'tipo_pago'      => $datos['tipo_pago']      ?? null,
                ],
            ]);
            $ctx = '[Sistema-reserva: ' . json_encode(json_decode((string) $res->getBody(), true), JSON_UNESCAPED_UNICODE) . ']';
            $historial[] = ['role' => 'user', 'content' => $msgUsuario . "\n\n" . $ctx];
            return $this->llamarClaude($systemPrompt, $historial, $nombre) ?: $respuesta;
        } catch (\Exception $e) {
            Log::error('[Bot] Error creando reserva: ' . $e->getMessage());
            return $respuesta;
        }
    }

    // ─────────────────────────────────────────────────────────────
    // INTERNOS — Conversación
    // ─────────────────────────────────────────────────────────────

    private function obtenerConversacion(string $usuarioId, string $nombre)
    {
        $conv = DB::table('bot_conversaciones')
            ->where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->orderBy('id', 'desc')
            ->first();

        if (!$conv) {
            $id = DB::table('bot_conversaciones')->insertGetId([
                'usuario_id'     => $usuarioId,
                'canal'          => 'whatsapp',
                'paso'           => 0,
                'nombre_cliente' => $nombre !== 'Cliente' ? $nombre : null,
                'activo'         => 1,
                'historial_json' => '[]',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $conv = DB::table('bot_conversaciones')->find($id);
        }
        return $conv;
    }

    private function persistirHistorial(int $convId, string $msgUsuario, string $msgBot, array $historial, array $datos)
    {
        $update = [
            'ultimo_mensaje' => $msgUsuario,
            'historial_json' => json_encode($historial, JSON_UNESCAPED_UNICODE),
            'updated_at'     => now(),
        ];
        $mapeo = [
            'nombre'           => 'nombre_cliente',
            'telefono'         => 'telefono',
            'email'            => 'correo',
            'programa_id'      => 'id_programa',
            'personas'         => 'cantidad_personas',
            'fecha'            => 'fecha_visita',
            'acepta_politicas' => 'politicas_aceptadas',
        ];
        foreach ($mapeo as $clave => $col) {
            if (!empty($datos[$clave])) {
                $update[$col] = $datos[$clave];
            }
        }
        if (!empty($datos['id_reserva'])) {
            $update['id_reserva']    = $datos['id_reserva'];
            $update['activo']        = 0;
            $update['motivo_cierre'] = 'reserva_creada';
        }
        DB::table('bot_conversaciones')->where('id', $convId)->update($update);
    }

    // ─────────────────────────────────────────────────────────────
    // INTERNOS — Programas
    // ─────────────────────────────────────────────────────────────

    private function cargarProgramasBd()
    {
        try {
            // incluye_masajes / incluye_almuerzos ya no son columnas físicas:
            // se derivan en vivo desde la relación programa_servicio -> servicios
            // (ver Programa::getIncluyeMasajesAttribute / getIncluyeAlmuerzosAttribute).
            $programas = Programa::where('estado', 'activo')
                ->with('servicios')
                ->orderBy('valor_programa')
                ->get();

            $resultado = [];
            foreach ($programas as $programa) {
                // NOTA (confirmado 31-08-2026): valor_programa ya es el precio final,
                // no se resta descuento (esa columna no representa un descuento activo).
                $precio = (int) ($programa->valor_programa ?? 0);

                $resultado[] = [
                    'id'                => $programa->id,
                    'nombre'            => $programa->nombre_programa,
                    'precio'            => $precio,
                    'precio_formato'    => '$' . number_format($precio, 0, ',', '.'),
                    'espacio_tipo'      => $programa->espacio_tipo,
                    'servicios'         => $programa->servicios->sortBy('nombre_servicio')->pluck('nombre_servicio')->values()->all(),
                    'incluye_masajes'   => (bool) $programa->incluye_masajes,
                    'incluye_almuerzos' => (bool) $programa->incluye_almuerzos,
                ];
            }
            return $resultado;
        } catch (\Exception $e) {
            Log::error('[Bot] Error cargando programas: ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // INTERNOS — Menú opciones
    // ─────────────────────────────────────────────────────────────

    /**
     * Carga todos los productos activos agrupados por tipo (entrada/fondo/acompañamiento).
     * Retorna array con 'entradas', 'fondos', 'acompañamientos', cada uno con id+nombre.
     */
    private function cargarMenuOpciones()
    {
        try {
            $rows = DB::table('productos as p')
                ->join('tipos_productos as tp', 'tp.id', '=', 'p.id_tipo_producto')
                ->where(function ($q) {
                    $q->where('p.estado', 'activo')->orWhereNull('p.estado');
                })
                ->whereIn('tp.nombre', ['entrada', 'fondo', 'acompañamiento'])
                ->select('p.id', 'p.nombre', 'tp.nombre as tipo')
                ->orderBy('tp.nombre')
                ->orderBy('p.id')
                ->get();

            $entradas       = [];
            $fondos         = [];
            $acompañamientos = [];

            foreach ($rows as $row) {
                $item = ['id' => $row->id, 'nombre' => $row->nombre];
                if ($row->tipo === 'entrada') {
                    $entradas[] = $item;
                } elseif ($row->tipo === 'fondo') {
                    $fondos[] = $item;
                } elseif ($row->tipo === 'acompañamiento') {
                    $acompañamientos[] = $item;
                }
            }

            return [
                'entradas'        => $entradas,
                'fondos'          => $fondos,
                'acompañamientos' => $acompañamientos,
            ];
        } catch (\Exception $e) {
            Log::error('[Bot] Error cargando menú opciones: ' . $e->getMessage());
            return ['entradas' => [], 'fondos' => [], 'acompañamientos' => []];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────

    private function normalizarTelefono(string $telefono)
    {
        $limpio = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($limpio) === 9 && substr($limpio, 0, 1) === '9') {
            $limpio = '56' . $limpio;
        }
        return $limpio;
    }

    private function generarCorreoTemporal(string $telefono, string $canal)
    {
        $base = $telefono ?: uniqid('bot');
        return "bot_{$canal}_{$base}@temporal.botacura.cl";
    }

    private function esFechaValida(string $fecha)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}
