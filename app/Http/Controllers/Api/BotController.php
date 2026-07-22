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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
            ->leftJoin('programa_servicio as ps', 'ps.id_programa', '=', 'p.id')
            ->leftJoin('servicios as s', 's.id', '=', 'ps.id_servicio')
            ->select('p.id', 'p.nombre_programa', 'p.slug', 'p.valor_programa', 'p.descuento', 's.nombre_servicio', 's.duracion')
            ->where('p.estado', 'activo')
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

        // Si viene programa_id, usar la lógica de DisponibilidadController
        if ($request->filled('programa_id')) {
            return app(DisponibilidadController::class)->check($request);
        }

        // Sin programa_id: contar reservas totales del día
        $cuposMax       = 16; // slots de tinaja
        $reservasActual = Reserva::whereDate('fecha_visita', $fecha)->count();
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

        $validator = Validator::make($request->all(), [
            'telefono' => 'required|string',
            'mensaje'  => 'required|string|max:4000',
            'nombre'   => 'nullable|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['ok' => false, 'errors' => $validator->errors()], 422);
        }

        $usuarioId = $this->normalizarTelefono($request->input('telefono', ''));
        $mensaje   = trim($request->mensaje);
        $nombre    = $request->nombre ?? 'Cliente';

        // Cargar/crear conversación
        $conv = $this->obtenerConversacion($usuarioId, $nombre);

        // Historial para Claude
        $historial   = json_decode($conv->historial_json ?? '[]', true) ?: [];
        $historial[] = ['role' => 'user', 'content' => $mensaje];

        // System prompt con programas + opciones de menú desde BD
        $programas    = $this->cargarProgramasBd();
        $menuOpciones = $this->cargarMenuOpciones();
        $promptSvc    = new BotPromptService();
        $systemPrompt = $promptSvc->getSystemPrompt($programas, $menuOpciones);

        // ── Cliente recurrente: inyectar datos ya conocidos ───────────────────
        $clienteExistente = DB::table('clientes')
            ->where('whatsapp_cliente', $usuarioId)
            ->whereNotNull('nombre_cliente')
            ->orderBy('id', 'desc')
            ->first(['nombre_cliente', 'correo', 'whatsapp_cliente']);

        if ($clienteExistente) {
            $systemPrompt .= "\n\n"
                . "════════════════════════════════════════════\n"
                . "CLIENTE REGISTRADO — DATOS YA CONOCIDOS\n"
                . "════════════════════════════════════════════\n"
                . "Este cliente ya tiene reservas anteriores. Sus datos están en el sistema:\n"
                . "  nombre:   {$clienteExistente->nombre_cliente}\n"
                . "  correo:   {$clienteExistente->correo}\n"
                . "  teléfono: {$clienteExistente->whatsapp_cliente}\n\n"
                . "REGLAS:\n"
                . "- NO pidas nombre, correo ni teléfono — ya los tenemos.\n"
                . "- Puedes saludarlo por su nombre directamente.\n"
                . "- Al crear la reserva, usa SIEMPRE estos datos exactos.\n"
                . "- Si el cliente quiere corregir algún dato, actualízalo y úsalo.";
        }

        // Precomputar calendario para evitar que Claude use su calendario erróneo
        $fechaHoy = \Carbon\Carbon::now(); // timezone configurado en APP_TIMEZONE=America/Santiago
        $diasOperativos = [0, 4, 5, 6]; // dom=0, jue=4, vie=5, sab=6

        // Tabla 1: Referencia completa de los próximos 35 días (TODOS los días, para que Claude
        // pueda verificar el día de la semana de cualquier número que mencione el cliente)
        $lineasRef = [];
        $cursorRef = $fechaHoy->copy();
        for ($i = 0; $i < 35; $i++) {
            $esHoy     = $cursorRef->format('Y-m-d') === $fechaHoy->format('Y-m-d');
            $esMañana  = $cursorRef->format('Y-m-d') === $fechaHoy->copy()->addDay()->format('Y-m-d');
            $operativo = in_array($cursorRef->dayOfWeek, $diasOperativos);
            $tag       = $esHoy ? ' ← HOY' : ($esMañana ? ' ← MAÑANA' : ($operativo ? '' : ' [no operativo: ' . $cursorRef->locale('es')->isoFormat('dddd') . ']'));
            $lineasRef[] = "  {$cursorRef->format('Y-m-d')}  {$cursorRef->locale('es')->isoFormat('dddd D [de] MMMM')}{$tag}";
            $cursorRef->addDay();
        }

        // Tabla 2: Solo días operativos con datos.fecha para matching
        $lineasOp = [];
        $cursor = $fechaHoy->copy();
        if (in_array($cursor->dayOfWeek, $diasOperativos)) {
            $lineasOp[] = "  \"{$cursor->locale('es')->isoFormat('dddd')} {$cursor->day} de {$cursor->locale('es')->isoFormat('MMMM')}\" (HOY)  →  datos.fecha = \"{$cursor->format('Y-m-d')}\"";
        }
        $cursor->addDay();
        while (count($lineasOp) < 14) {
            if (in_array($cursor->dayOfWeek, $diasOperativos)) {
                $esMañana = $cursor->format('Y-m-d') === $fechaHoy->copy()->addDay()->format('Y-m-d') ? ' (MAÑANA)' : '';
                $lineasOp[] = "  \"{$cursor->locale('es')->isoFormat('dddd')} {$cursor->day} de {$cursor->locale('es')->isoFormat('MMMM')}\"{$esMañana}  →  datos.fecha = \"{$cursor->format('Y-m-d')}\"";
            }
            $cursor->addDay();
        }

        $systemPrompt .= "\n\n"
            . "════════════════════════════════════════════\n"
            . "CALENDARIO — ÚNICA FUENTE DE VERDAD\n"
            . "════════════════════════════════════════════\n"
            . "HOY ES: " . $fechaHoy->format('Y-m-d') . " ("
            . $fechaHoy->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY') . ")\n\n"
            . "⚠️ NUNCA uses tu propio cálculo de días de la semana para 2026.\n"
            . "Usa EXCLUSIVAMENTE esta tabla para saber qué día cae cada número.\n\n"
            . "TABLA DE REFERENCIA (todos los días, para verificar día de semana):\n"
            . implode("\n", $lineasRef) . "\n\n"
            . "DÍAS OPERATIVOS CON CUPO (para reservas):\n"
            . implode("\n", $lineasOp) . "\n\n"
            . "REGLAS DE MATCHING:\n"
            . "1. Cuando el cliente menciona una fecha (ej: 'domingo 27'), busca el número 27\n"
            . "   en la TABLA DE REFERENCIA para saber el día real.\n"
            . "2. Si el día real coincide con lo que dijo el cliente → fecha correcta.\n"
            . "3. Si el día real NO coincide (ej: '27' es lunes, no domingo) → el cliente\n"
            . "   se equivocó en el nombre del día. NO digas 'ya pasó'. Corrige:\n"
            . "   'El 27 de julio cae en lunes 😊 ¿Quiso decir domingo 26 o lunes 27?'\n"
            . "4. Si la fecha ya pasó (anterior a HOY) → ofrece el primer día operativo de la lista.\n"
            . "5. NUNCA uses una fecha que no esté en la TABLA DE REFERENCIA.\n\n"
            . "REGLA 'MAÑANA' (CRÍTICO):\n"
            . "- Solo llames 'mañana' a la fecha marcada '← MAÑANA' en la TABLA DE REFERENCIA.\n"
            . "- Si esa fecha no es operativa, NO uses 'mañana' para ninguna otra fecha.\n"
            . "- Para cualquier otra fecha futura usa el día completo: 'el domingo 26 de julio'.\n"
            . "- NUNCA digas 'mañana domingo', 'mañana sábado', etc. si la fecha marcada como\n"
            . "  '← MAÑANA' no es ese día.";

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

    private function llamarClaude(string $systemPrompt, array $historial, string $nombre)
    {
        $apiKey = config('services.anthropic.key');
        $model  = config('services.anthropic.model', 'claude-haiku-4-5-20251001');

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 30]);
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

            $body    = json_decode($response->getBody()->getContents(), true);
            $content = $body['content'][0]['text'] ?? '';

            // 1) Quitar bloques de código markdown si los hay
            $content = trim(preg_replace(['/^```json\s*/i', '/\s*```$/'], '', trim($content)));

            // 2) Intentar parsear directamente
            $parsed = json_decode($content, true);

            // 3) Si falla (Claude añadió texto antes/después del JSON), extraer el objeto
            if (!$parsed) {
                if (preg_match('/\{.*\}/s', $content, $m)) {
                    $parsed = json_decode($m[0], true);
                }
            }

            if (!$parsed || !isset($parsed['accion'], $parsed['mensaje'])) {
                // Limpiar cualquier JSON que pudiera estar en el texto antes de enviarlo al usuario
                $textoLimpio = is_array($parsed) ? json_encode($parsed, JSON_UNESCAPED_UNICODE) : $content;
                return ['accion' => 'responder', 'mensaje' => $textoLimpio, 'datos' => []];
            }

            // 4) Asegurar que mensaje sea string limpio (no JSON anidado)
            if (is_array($parsed['mensaje'])) {
                $parsed['mensaje'] = implode("\n", $parsed['mensaje']);
            }
            return $parsed;

        } catch (\Exception $e) {
            Log::error('[Bot] Excepción Claude: ' . $e->getMessage());
            return null;
        }
    }

    private function procesarDisponibilidad(array $respuesta, string $systemPrompt, array $historial, string $msgUsuario, string $nombre)
    {
        $datos = $respuesta['datos'] ?? [];

        if (empty($datos['fecha'])) {
            return $respuesta;
        }

        // Resolver programa_id: Claude puede enviarlo como número o como nombre
        $programaId = $datos['programa_id'] ?? null;
        if (!$programaId && !empty($datos['programa'])) {
            $prog = DB::table('programas')
                ->where('nombre_programa', 'like', '%' . $datos['programa'] . '%')
                ->where('estado', 'activo')
                ->first(['id']);
            $programaId = $prog ? $prog->id : null;
        }

        try {
            // Llamada directa (evita deadlock HTTP self-referencial en XAMPP)
            $queryParams = [
                'fecha'    => $datos['fecha'],
                'personas' => $datos['personas'] ?? 1,
            ];
            if ($programaId) {
                $queryParams['programa_id'] = $programaId;
            } else {
                // Sin programa aún: verificar disponibilidad general del día
                Log::info('[Bot] procesarDisponibilidad: sin programa_id, verificando disponibilidad general', $datos);
            }
            $fakeDispRequest = \Illuminate\Http\Request::create('/api/bot/disponibilidad', 'GET', $queryParams);
            $dispResponse = $this->disponibilidad($fakeDispRequest);
            $dispData     = json_decode($dispResponse->getContent(), true);
            $ctx      = '[Sistema-disponibilidad: ' . json_encode($dispData, JSON_UNESCAPED_UNICODE) . ']';
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

        // Para cliente existente, completar nombre/email desde DB si Claude no los tiene
        if (empty($datos['nombre']) || empty($datos['email'])) {
            $clienteDb = DB::table('clientes')
                ->where('whatsapp_cliente', $usuarioId)
                ->whereNotNull('nombre_cliente')
                ->orderBy('id', 'desc')
                ->first(['nombre_cliente', 'correo']);
            if ($clienteDb) {
                $datos['nombre'] = $datos['nombre'] ?? $clienteDb->nombre_cliente;
                $datos['email']  = $datos['email']  ?? $clienteDb->correo;
            }
        }

        foreach (['nombre', 'email', 'programa_id', 'fecha', 'personas'] as $campo) {
            if (empty($datos[$campo])) {
                return $respuesta;
            }
        }
        try {
            // Llamada directa (evita deadlock HTTP self-referencial en XAMPP)
            $reservaController = new \App\Http\Controllers\Api\BotReservaController();
            $fakeRequest = \Illuminate\Http\Request::create('/api/bot/reserva', 'POST', [
                'nombre'             => $datos['nombre'],
                'telefono'           => $datos['telefono'] ?? $usuarioId,
                'email'              => $datos['email'],
                'programa_id'        => $datos['programa_id'],
                'fecha'              => $datos['fecha'],
                'personas'           => $datos['personas'],
                'masajes_extra'      => (int)  ($datos['masajes_extra']      ?? 0),
                'menus_extra'        => (int)  ($datos['menus_extra']        ?? 0),
                'tipo_servicio'      =>         $datos['tipo_servicio']      ?? null,
                'alimentacion_extra' => (bool) ($datos['alimentacion_extra'] ?? false),
                'almuerzo_extra'     => (bool) ($datos['almuerzo_extra']     ?? false),
                'estacion_extra'     => (bool) ($datos['estacion_extra']     ?? false),
                'sauna_extra'        => (bool) ($datos['sauna_extra']        ?? false),
                'tinaja_extra'       => (bool) ($datos['tinaja_extra']       ?? false),
            ]);
            $response = $reservaController->store($fakeRequest);
            $resData  = json_decode($response->getContent(), true);
            // Inyectar el teléfono capturado de WhatsApp para que Claude lo incluya en la confirmación
            $resData['telefono_cliente'] = $usuarioId;
            $ctx      = '[Sistema-reserva: ' . json_encode($resData, JSON_UNESCAPED_UNICODE) . ']';
            $historial[] = ['role' => 'user', 'content' => $msgUsuario . "\n\n" . $ctx];
            $finalResp = $this->llamarClaude($systemPrompt, $historial, $nombre) ?: $respuesta;
            // Pasar enlace_pago siempre que exista (reserva_id es null hasta confirmar pago)
            if (!empty($resData['enlace_pago'])) {
                $finalResp['datos'] = array_merge($finalResp['datos'] ?? [], [
                    'reserva_id'  => $resData['reserva_id']  ?? null,
                    'enlace_pago' => $resData['enlace_pago'],
                ]);
            }
            return $finalResp;
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
                'telefono'       => $usuarioId,
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
                // fecha_visita debe ser YYYY-MM-DD válida — nunca texto descriptivo
                if ($col === 'fecha_visita') {
                    $val = $datos[$clave];
                    $d   = \DateTime::createFromFormat('Y-m-d', $val);
                    if (!$d || $d->format('Y-m-d') !== $val) {
                        Log::warning("[Bot] fecha_visita inválida ignorada: '{$val}'");
                        continue;
                    }
                }
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
            $filas = DB::table('programas as p')
                ->leftJoin('programa_servicio as ps', 'ps.id_programa', '=', 'p.id')
                ->leftJoin('servicios as s', 's.id', '=', 'ps.id_servicio')
                ->select('p.id', 'p.nombre_programa', 'p.valor_programa', 'p.espacio_tipo', 's.nombre_servicio')
                ->where('p.estado', 'activo')
                ->orderBy('p.valor_programa')
                ->orderBy('s.nombre_servicio')
                ->get();

            $agrupados = [];
            foreach ($filas as $fila) {
                $id = $fila->id;
                if (!isset($agrupados[$id])) {
                    $agrupados[$id] = [
                        'id'             => $id,
                        'nombre'         => $fila->nombre_programa,
                        'precio'         => (int) $fila->valor_programa,
                        'precio_formato' => '$' . number_format((int) $fila->valor_programa, 0, ',', '.'),
                        'espacio_tipo'   => $fila->espacio_tipo,
                        'servicios'      => [],
                    ];
                }
                if ($fila->nombre_servicio) {
                    $agrupados[$id]['servicios'][] = $fila->nombre_servicio;
                }
            }
            return array_values($agrupados);
        } catch (\Exception $e) {
            Log::error('[Bot] Error cargando programas: ' . $e->getMessage());
            return [];
        }
    }

    private function cargarMenuOpciones(): array
    {
        try {
            $productos = DB::table('productos as p')
                ->join('tipos_productos as t', 't.id', '=', 'p.id_tipo_producto')
                ->where('p.estado', 'activo')
                ->select('p.id', 'p.nombre', 't.nombre as tipo')
                ->orderBy('t.nombre')
                ->orderBy('p.nombre')
                ->get();

            $entradas = $fondos = $acompañamientos = [];
            foreach ($productos as $p) {
                $item = ['id' => $p->id, 'nombre' => $p->nombre];
                switch (strtolower($p->tipo)) {
                    case 'entrada':        $entradas[]        = $item; break;
                    case 'fondo':          $fondos[]          = $item; break;
                    case 'acompañamiento': $acompañamientos[] = $item; break;
                }
            }
            return compact('entradas', 'fondos', 'acompañamientos');
        } catch (\Exception $e) {
            Log::error('[Bot] Error cargando menú opciones: ' . $e->getMessage());
            return [];
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
