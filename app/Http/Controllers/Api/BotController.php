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

    /** Imágenes que el sistema adjunta a la respuesta (fotos de ubicaciones). */
    private $imagenes = [];

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
        // Festivos habilitados en el calendario del panel también operan
        $esOperativo = in_array($diaSemana, [0, 4, 5, 6])
            || \App\FechaDisponible::where('fecha', $fecha)->where('habilitada', true)->exists();

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
        $valorPersona = $programa ? (int) $programa->valor_programa : 0;
        // Temporada de verano (noviembre a febrero): +$5.000 por persona en programas regulares.
        // No aplica a uso exclusivo ni a programas de semana (S), cuyo valor de verano aún no está definido.
        $mesVisita = (int) Carbon::parse($request->fecha_visita)->month;
        $nombreProg = trim((string) ($programa->nombre_programa ?? ''));
        if ($programa && in_array($mesVisita, [11, 12, 1, 2], true)
            && stripos($nombreProg, 'exclusivo') === false
            && !preg_match('/\\sS$/i', $nombreProg)) {
            $valorPersona += 5000;
        }
        $valorTotal = $valorPersona * (int) $request->cantidad_personas;
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
    // POST /api/bot-ai/message
    // Body: { telefono, mensaje, nombre?, simulacion? }
    // simulacion=true: no crea holds/reservas, no reagenda, no deriva ni alerta
    // (lo usan la batería de pruebas y el chat de prueba del backoffice).
    // ─────────────────────────────────────────────────────────────
    public function message(Request $request)
    {
        $secret = config('services.bot.secret');
        if ($secret && $request->header(self::BOT_SECRET_HEADER) !== $secret) {
            return response()->json(['ok' => false, 'error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'telefono' => 'required|string',
            'mensaje'  => 'required|string|max:4000',
            'nombre'   => 'nullable|string|max:100',
        ]);

        $simulacion = filter_var($request->input('simulacion', false), FILTER_VALIDATE_BOOLEAN);
        $usuarioId  = $this->normalizarTelefono($request->telefono);
        $mensaje    = trim($request->mensaje);
        $nombre     = $request->nombre ?? 'Cliente';

        $conv      = $this->obtenerConversacion($usuarioId, $nombre);
        $historial = json_decode($conv->historial_json ?? '[]', true) ?: [];

        $contenidoParaHistorial = $mensaje;
        if (empty($historial)) {
            $ctxTelefono = "[Sistema: El cliente te escribe desde el WhatsApp {$usuarioId} — "
                . "ese ES su teléfono de contacto, ya lo tienes. En el paso de teléfono NO lo "
                . "preguntes desde cero: solo confírmalo brevemente, salvo que el cliente prefiera dar otro número.]";
            $clienteConocido = $this->buscarClientePorTelefono($usuarioId);
            if ($clienteConocido) {
                $correo = (!empty($clienteConocido->correo) && strpos($clienteConocido->correo, '@temporal.') === false) ? $clienteConocido->correo : null;
                $ctxCliente = "[Sistema: Este número ya es cliente de Botacura — nombre: {$clienteConocido->nombre_cliente}"
                    . ($correo ? ", correo: {$correo}" : '')
                    . ". Salúdalo como cliente que vuelve, usando su nombre. No le pidas de nuevo nombre"
                    . ($correo ? ' ni correo' : '') . "; solo confírmalos brevemente si hace falta.]";
                $contenidoParaHistorial = $mensaje . "\n\n" . $ctxCliente . "\n\n" . $ctxTelefono;
            } else {
                $contenidoParaHistorial = $mensaje . "\n\n" . $ctxTelefono;
            }
        }
        $historial[] = ['role' => 'user', 'content' => $contenidoParaHistorial];

        $programas      = $this->cargarProgramasBd();
        $menuOpciones   = $this->cargarMenuOpciones();
        $contexto       = $this->contextoCliente($usuarioId);
        $systemPrompt   = (new BotPromptService())->getSystemPrompt($programas, $menuOpciones, $contexto['texto']);

        $respuesta = $this->llamarClaude($systemPrompt, $historial, $nombre);
        if (!$respuesta) {
            $msgError = 'Estamos teniendo problemas técnicos. Por favor escríbenos al +56 9 7448 4112 🙏';
            $historial[] = ['role' => 'assistant', 'content' => $msgError];
            $this->persistirHistorial($conv->id, $mensaje, $msgError, $historial, []);
            if (!$simulacion) {
                $this->escalar($usuarioId, $nombre, 'Error al llamar a la IA', $mensaje);
            }
            return response()->json(['ok' => true, 'accion' => 'escalar_humano', 'mensaje' => $msgError, 'datos' => ['motivo' => 'error_claude_api']]);
        }

        // Acciones encadenadas: el sistema ejecuta y le devuelve el resultado a la IA.
        $ejecutadas = [];
        $acciones   = [];
        for ($i = 0; $i < 4; $i++) {
            $accion = $respuesta['accion'] ?? 'responder';
            if (!in_array($accion, ['verificar_disponibilidad', 'cotizar', 'ubicaciones', 'crear_reserva', 'reagendar', 'guardar_seleccion_menu'], true)) {
                break;
            }
            $firma = $accion . ':' . md5(json_encode($respuesta['datos'] ?? []));
            if (isset($ejecutadas[$firma]) || ($accion === 'crear_reserva' && isset($ejecutadas['crear_reserva']))) {
                // La IA repitió una acción ya ejecutada: pedirle la respuesta final al cliente.
                $h = $historial;
                $h[] = ['role' => 'assistant', 'content' => json_encode($respuesta, JSON_UNESCAPED_UNICODE)];
                $h[] = ['role' => 'user', 'content' => '[Sistema: la acción "' . $accion . '" YA se ejecutó y su resultado está arriba. No la repitas. Responde ahora al cliente con accion "responder" usando ese resultado.]'];
                $final = $this->llamarClaude($systemPrompt, $h, $nombre);
                if ($final && trim((string) ($final['mensaje'] ?? '')) !== '' && ($final['accion'] ?? '') !== $accion) {
                    $respuesta = $final;
                }
                break;
            }
            $ejecutadas[$firma] = true;
            if ($accion === 'crear_reserva') {
                $ejecutadas['crear_reserva'] = true;
            }
            $ctx = $this->ejecutarAccion($accion, $respuesta['datos'] ?? [], $usuarioId, $contexto, $simulacion);
            if ($ctx === null) {
                break;
            }
            $acciones[] = $accion;
            // El resultado del sistema queda guardado en el historial (memoria del bot).
            $ultimo = count($historial) - 1;
            $historial[$ultimo]['content'] .= "\n\n" . $ctx;
            $nueva = $this->llamarClaude($systemPrompt, $historial, $nombre);
            if (!$nueva) {
                break;
            }
            $respuesta = $nueva;
        }

        if (trim((string) ($respuesta['mensaje'] ?? '')) === '') {
            $h = $historial;
            $h[] = ['role' => 'user', 'content' => '[Sistema: tu última respuesta no traía mensaje para el cliente. Responde con accion "responder" y un mensaje basado en los resultados del sistema de arriba.]'];
            $final = $this->llamarClaude($systemPrompt, $h, $nombre);
            if ($final && trim((string) ($final['mensaje'] ?? '')) !== '') {
                $respuesta = $final;
            } else {
                $respuesta = ['accion' => 'escalar_humano', 'mensaje' => 'Déjame revisarlo con el equipo y te escribimos en breve 🙏', 'datos' => ['motivo' => 'Respuesta vacía de la IA']];
            }
        }

        // Control de montos: todo "$" del mensaje debe venir del sistema.
        $noVerificados = $this->montosNoVerificados($respuesta['mensaje'] ?? '', $systemPrompt, $historial);
        if ($noVerificados) {
            Log::warning('[Bot] Montos no verificados en respuesta', ['telefono' => $usuarioId, 'montos' => $noVerificados]);
            $hist2   = $historial;
            $hist2[] = ['role' => 'assistant', 'content' => json_encode($respuesta, JSON_UNESCAPED_UNICODE)];
            $hist2[] = ['role' => 'user', 'content' => '[Sistema-validador: tu mensaje incluía montos que el sistema no calculó (' . implode(', ', array_map(function ($n) { return '$' . number_format($n, 0, ',', '.'); }, $noVerificados)) . '). No calcules montos tú. Si necesitas un total o abono, usa la acción "cotizar"; si no, responde sin esos montos. Responde de nuevo en el formato JSON.]'];
            $reintento = $this->llamarClaude($systemPrompt, $hist2, $nombre);
            if ($reintento && in_array($reintento['accion'] ?? '', ['cotizar'], true) && !isset($ejecutadas['cotizar_reintento'])) {
                $ctx = $this->ejecutarAccion('cotizar', $reintento['datos'] ?? [], $usuarioId, $contexto, $simulacion);
                $historial[count($historial) - 1]['content'] .= "\n\n" . $ctx;
                $reintento = $this->llamarClaude($systemPrompt, $historial, $nombre);
            }
            if ($reintento && !$this->montosNoVerificados($reintento['mensaje'] ?? '', $systemPrompt, $historial)) {
                $respuesta = $reintento;
            } else {
                $respuesta = [
                    'accion'  => 'escalar_humano',
                    'mensaje' => 'Déjame confirmar el valor exacto con el equipo y te escribimos en breve 🙏',
                    'datos'   => ['motivo' => 'Monto no verificado por el sistema: ' . implode(', ', $noVerificados)],
                ];
            }
        }

        if (($respuesta['accion'] ?? '') === 'escalar_humano' && !$simulacion) {
            $this->escalar($usuarioId, $nombre, (string) ($respuesta['datos']['motivo'] ?? 'Derivado por el bot'), $mensaje);
        }

        $historial[] = ['role' => 'assistant', 'content' => $respuesta['mensaje'] ?? ''];
        if (count($historial) > self::MAX_HISTORY_TURNS * 2) {
            $historial = array_slice($historial, -(self::MAX_HISTORY_TURNS * 2));
            while ($historial && ($historial[0]['role'] ?? '') !== 'user') {
                array_shift($historial);
            }
        }

        $this->persistirHistorial($conv->id, $mensaje, $respuesta['mensaje'] ?? '', $historial, $simulacion ? [] : ($respuesta['datos'] ?? []));

        return response()->json(array_merge(['ok' => true, 'acciones_ejecutadas' => $acciones, 'imagenes' => $this->imagenes], $respuesta));
    }

    /**
     * Ejecuta una acción pedida por la IA y devuelve el texto "[Sistema-...]" con el resultado.
     */
    private function ejecutarAccion(string $accion, array $datos, string $usuarioId, array $contexto, bool $simulacion): ?string
    {
        try {
            if ($accion === 'cotizar') {
                $cot = \App\Services\BotTarifasService::cotizar($datos);
                return '[Sistema-cotizacion: ' . json_encode($cot, JSON_UNESCAPED_UNICODE) . ' — usa EXACTAMENTE estos montos (campos *_formato). Si "ok" es false, explica el motivo de "errores" al cliente y no ofrezcas reservar ese caso.]';
            }

            if ($accion === 'ubicaciones') {
                if (empty($datos['programa_id']) || empty($datos['fecha']) || empty($datos['personas'])) {
                    return '[Sistema-ubicaciones: {"ok":false,"motivo":"Faltan programa_id, fecha o personas."}]';
                }
                $plan = \App\Services\BotUbicacionesService::plan((string) $datos['fecha'], (int) $datos['programa_id'], (int) $datos['personas'], $datos['preferencia'] ?? null);
                $fotos = [];
                $tipos = $plan['requiere_eleccion'] ? ['terraza', 'reposera'] : ($plan['tipo_foto'] ? [$plan['tipo_foto']] : []);
                foreach ($tipos as $t) {
                    $url = \App\Services\BotUbicacionesService::fotoUrl($t);
                    if ($url) {
                        $this->imagenes[] = ['url' => $url, 'texto' => \App\Services\BotUbicacionesService::fotoTexto($t)];
                        $fotos[] = $t;
                    }
                }
                unset($plan['reasignar'], $plan['asignacion']);
                $plan['fotos_enviadas'] = $fotos;
                return '[Sistema-ubicaciones: ' . json_encode($plan, JSON_UNESCAPED_UNICODE) . ' — Si requiere_eleccion es true, pregunta qué prefieren (terraza o reposera)'
                    . ($fotos ? '; el sistema ya envió las fotos, menciónalo brevemente' : '; no hay fotos cargadas, describe ambas opciones en una línea')
                    . '. Si es false y ok es true, cuéntale en una línea dónde estarán (texto_cliente). Si ok es false, usa escalar_humano. NUNCA nombres una estación o terraza específica salvo que texto_cliente lo diga.]';
            }

            if ($accion === 'verificar_disponibilidad') {
                if (empty($datos['fecha'])) {
                    return null;
                }
                $cot = \App\Services\BotTarifasService::cotizar([
                    'programa_id' => $datos['programa_id'] ?? null,
                    'fecha'       => $datos['fecha'],
                    'personas'    => $datos['personas'] ?? 1,
                ]);
                $reglas = array_values(array_filter($cot['errores'], function ($e) {
                    return in_array($e['codigo'], ['grupo_grande', 'agenda_no_abierta', 'fecha_pasada', 'fecha_no_habilitada', 'fecha_invalida'], true);
                }));
                if ($reglas) {
                    return '[Sistema-disponibilidad: ' . json_encode(['disponible' => false, 'motivos' => $reglas], JSON_UNESCAPED_UNICODE) . ']';
                }
                $query = ['fecha' => $datos['fecha'], 'personas' => $datos['personas'] ?? 1];
                if (!empty($datos['programa_id'])) {
                    $query['programa_id'] = $datos['programa_id'];
                }
                $res = $this->disponibilidad(new Request($query));
                return '[Sistema-disponibilidad: ' . $res->getContent() . ']';
            }

            if ($accion === 'crear_reserva') {
                foreach (['nombre', 'email', 'programa_id', 'fecha', 'personas'] as $campo) {
                    if (empty($datos[$campo])) {
                        return '[Sistema-reserva: {"ok":false,"error":"faltan_datos","motivo":"Falta ' . $campo . '"}]';
                    }
                }
                $cot = \App\Services\BotTarifasService::cotizar($datos);
                if (!$cot['ok']) {
                    return '[Sistema-reserva: ' . json_encode(['ok' => false, 'errores' => $cot['errores']], JSON_UNESCAPED_UNICODE) . ']';
                }
                $planUb = \App\Services\BotUbicacionesService::plan((string) $datos['fecha'], (int) $datos['programa_id'], (int) $datos['personas'], $datos['ubicacion_preferida'] ?? null);
                if ($planUb['categoria'] && !$planUb['ok']) {
                    return '[Sistema-reserva: ' . json_encode(['ok' => false, 'error' => 'ubicacion_no_disponible', 'motivo' => $planUb['motivo']], JSON_UNESCAPED_UNICODE) . ']';
                }
                if ($planUb['requiere_eleccion']) {
                    return '[Sistema-reserva: {"ok":false,"error":"falta_ubicacion","motivo":"Antes de reservar pregunta si prefieren terraza o reposera (acción ubicaciones) y envía ubicacion_preferida."}]';
                }
                if ($simulacion) {
                    $esTransf = \App\Services\BotTarifasService::esTransferencia($datos['tipo_pago'] ?? '');
                    return '[Sistema-reserva (SIMULACIÓN, no se creó nada): ' . json_encode(array_merge($cot, [
                        'ok' => true, 'es_hold' => $esTransf, 'reserva_id' => $esTransf ? null : 0,
                        'valor_total_formato' => $cot['total_formato'], 'abono_50_formato' => $cot['pago_ahora_formato'],
                        'diferencia_formato' => $cot['saldo_formato'], 'hold_minutos' => 30,
                        'webpay_url' => $esTransf ? null : 'https://webpay.simulacion/xxxx',
                        'ubicacion_texto' => $planUb['texto_cliente'],
                    ]), JSON_UNESCAPED_UNICODE) . ']';
                }
                $payload = [
                    'nombre'        => $datos['nombre'],
                    'telefono'      => !empty($datos['telefono']) ? $datos['telefono'] : $usuarioId,
                    'email'         => $datos['email'],
                    'programa_id'   => $datos['programa_id'],
                    'fecha'         => $datos['fecha'],
                    'personas'      => $datos['personas'],
                    'masajes_extra' => $datos['masajes_extra'] ?? 0,
                    'desayuno_once' => $datos['desayuno_once'] ?? 0,
                    'desayuno_tipo' => $datos['desayuno_tipo'] ?? null,
                    'observacion'   => $datos['observacion'] ?? null,
                    'tipo_pago'     => $datos['tipo_pago'] ?? null,
                    'ubicacion_preferida' => in_array($datos['ubicacion_preferida'] ?? null, ['terraza', 'reposera'], true) ? $datos['ubicacion_preferida'] : null,
                ];
                $client = new GuzzleClient(['timeout' => 20, 'http_errors' => false]);
                $res = $client->post(url('/api/bot-ai/reserva'), [
                    'headers' => [self::BOT_SECRET_HEADER => config('services.bot.secret'), 'X-Bot-Token' => env('BOT_API_TOKEN'), 'content-type' => 'application/json', 'accept' => 'application/json'],
                    'json'    => $payload,
                ]);
                return '[Sistema-reserva: ' . json_encode(json_decode((string) $res->getBody(), true), JSON_UNESCAPED_UNICODE) . ']';
            }

            if ($accion === 'reagendar') {
                $id = (int) ($datos['reserva_id'] ?? 0);
                $nueva = (string) ($datos['nueva_fecha'] ?? '');
                if (!$id || !in_array($id, $contexto['reserva_ids'], true)) {
                    return '[Sistema-reagendar: {"ok":false,"error":"reserva_no_encontrada","mensaje":"Esa reserva no está asociada a este WhatsApp. Pide al cliente el número de reserva o deriva a una persona."}]';
                }
                $reserva = DB::table('reservas')->where('id', $id)->first();
                $cot = \App\Services\BotTarifasService::cotizar([
                    'programa_id' => $reserva->id_programa, 'fecha' => $nueva, 'personas' => $reserva->cantidad_personas,
                ]);
                $reglas = array_values(array_filter($cot['errores'], function ($e) {
                    return in_array($e['codigo'], ['agenda_no_abierta', 'fecha_pasada', 'fecha_no_habilitada', 'fecha_invalida'], true);
                }));
                if ($reglas) {
                    return '[Sistema-reagendar: ' . json_encode(['ok' => false, 'errores' => $reglas], JSON_UNESCAPED_UNICODE) . ']';
                }
                $check = $this->validarReagendamiento($reserva, $nueva);
                if (!$check['ok'] || $simulacion) {
                    return '[Sistema-reagendar' . ($simulacion ? ' (SIMULACIÓN)' : '') . ': ' . json_encode($check, JSON_UNESCAPED_UNICODE) . ']';
                }
                $res = $this->reprogramarReserva(new Request(['nueva_fecha' => $nueva]), $id);
                return '[Sistema-reagendar: ' . $res->getContent() . ']';
            }

            if ($accion === 'guardar_seleccion_menu') {
                if (empty($datos['reserva_id']) || $simulacion) {
                    return $simulacion ? '[Sistema-menu (SIMULACIÓN): {"ok":true}]' : null;
                }
                $client = new GuzzleClient(['timeout' => 10, 'http_errors' => false]);
                $res = $client->patch(url('/api/bot-ai/reserva/' . (int) $datos['reserva_id'] . '/menu-seleccion'), [
                    'headers' => [self::BOT_SECRET_HEADER => config('services.bot.secret'), 'X-Bot-Token' => env('BOT_API_TOKEN'), 'content-type' => 'application/json'],
                    'json'    => ['selecciones' => $datos['selecciones'] ?? []],
                ]);
                return '[Sistema-menu: ' . json_encode(json_decode((string) $res->getBody(), true), JSON_UNESCAPED_UNICODE) . ']';
            }
        } catch (\Throwable $e) {
            Log::error('[Bot] Error ejecutando acción ' . $accion . ': ' . $e->getMessage());
            return '[Sistema-error: no se pudo completar "' . $accion . '". No inventes el resultado: dile al cliente que lo revisará el equipo y usa escalar_humano.]';
        }
        return null;
    }

    /** Mismas reglas que reprogramarReserva(), sin escribir nada. */
    private function validarReagendamiento($reserva, string $nuevaFecha): array
    {
        if ($reserva->estado === 'cancelada') {
            return ['ok' => false, 'error' => 'cancelada', 'mensaje' => 'La reserva está cancelada.'];
        }
        if ($nuevaFecha === (string) $reserva->fecha_visita) {
            return ['ok' => false, 'error' => 'misma_fecha', 'mensaje' => 'La nueva fecha es igual a la actual.'];
        }
        if (DB::table('reagendamientos')->where('id_reserva', $reserva->id)->exists()) {
            return ['ok' => false, 'error' => 'limite_reprogramacion', 'mensaje' => 'Esta reserva ya fue reagendada una vez; no se permite una segunda.'];
        }
        $programa = DB::table('programas')->where('id', $reserva->id_programa)->first();
        if (($programa && isset($programa->permite_reprogramacion) && !$programa->permite_reprogramacion)
            || in_array((int) $reserva->id_programa, \App\Services\BotTarifasService::idsProgramasSemana(), true)) {
            return ['ok' => false, 'error' => 'programa_no_modificable', 'mensaje' => 'Este programa no admite cambios de fecha según las políticas.'];
        }
        $original = Carbon::parse($reserva->fecha_visita, 'America/Santiago');
        if (Carbon::now('America/Santiago')->greaterThanOrEqualTo($original->copy()->subDays(3)->setTime(10, 0, 0))) {
            return ['ok' => false, 'error' => 'fuera_de_plazo', 'mensaje' => 'Fuera de plazo: se debía pedir hasta el ' . $original->copy()->subDays(3)->locale('es')->isoFormat('dddd D [de] MMMM') . ' a las 10:00.'];
        }
        if (Carbon::parse($nuevaFecha, 'America/Santiago')->greaterThan($original->copy()->addDays(45))) {
            return ['ok' => false, 'error' => 'fuera_de_ventana', 'mensaje' => 'La nueva fecha debe estar dentro de los 45 días corridos siguientes a la fecha original (hasta el ' . $original->copy()->addDays(45)->locale('es')->isoFormat('D [de] MMMM') . ').'];
        }
        $chequeo = json_decode(app(DisponibilidadController::class)->check(new Request([
            'fecha' => $nuevaFecha, 'programa_id' => $reserva->id_programa, 'personas' => $reserva->cantidad_personas,
        ]))->getContent(), true);
        if (!($chequeo['disponible'] ?? false)) {
            return ['ok' => false, 'error' => 'sin_cupo', 'mensaje' => $chequeo['motivo_no_disponible'] ?? 'No hay cupo para la nueva fecha.'];
        }
        return ['ok' => true, 'mensaje' => 'Se puede reagendar a ' . $nuevaFecha . '.'];
    }

    private function buscarClientePorTelefono(string $tel)
    {
        $corto = substr($tel, -9);
        return DB::table('clientes')
            ->whereIn('whatsapp_cliente', array_unique([$tel, $corto, '+' . $tel, '56' . $corto]))
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Reservas y holds del cliente (por teléfono) para que el bot responda con datos reales:
     * fecha, programa, personas, pagos, saldo y si puede reagendar.
     */
    private function contextoCliente(string $tel): array
    {
        $corto = substr($tel, -9);
        $ids   = [];
        $lineas = [];
        try {
            $hoy = Carbon::now('America/Santiago');
            $reservas = DB::table('reservas as r')
                ->join('clientes as c', 'c.id', '=', 'r.cliente_id')
                ->leftJoin('programas as p', 'p.id', '=', 'r.id_programa')
                ->leftJoin('ventas as v', 'v.id_reserva', '=', 'r.id')
                ->whereIn('c.whatsapp_cliente', array_unique([$tel, $corto, '+' . $tel, '56' . $corto]))
                ->where('r.fecha_visita', '>=', $hoy->copy()->subDays(30)->toDateString())
                ->orderBy('r.fecha_visita')
                ->select('r.id', 'r.fecha_visita', 'r.cantidad_personas', 'r.estado', 'r.id_programa', 'p.nombre_programa',
                    'p.permite_reprogramacion', 'v.abono_programa', 'v.diferencia_programa', 'v.estado_pago', 'v.total_pagar')
                ->limit(5)
                ->get();
            foreach ($reservas as $r) {
                $ids[] = (int) $r->id;
                $f = Carbon::parse($r->fecha_visita, 'America/Santiago');
                $reagendada = DB::table('reagendamientos')->where('id_reserva', $r->id)->exists();
                $esS = in_array((int) $r->id_programa, \App\Services\BotTarifasService::idsProgramasSemana(), true);
                $limite = $f->copy()->subDays(3)->setTime(10, 0, 0);
                if ($r->estado === 'cancelada') {
                    $reag = 'no (cancelada)';
                } elseif ($reagendada) {
                    $reag = 'no (ya fue reagendada una vez)';
                } elseif ($esS || (isset($r->permite_reprogramacion) && !$r->permite_reprogramacion)) {
                    $reag = 'no (programa no reagendable)';
                } elseif ($hoy->greaterThanOrEqualTo($limite)) {
                    $reag = 'no (fuera de plazo; el plazo vencía el ' . $limite->locale('es')->isoFormat('dddd D [de] MMMM') . ' a las 10:00)';
                } else {
                    $reag = 'sí, hasta el ' . $limite->locale('es')->isoFormat('dddd D [de] MMMM') . ' a las 10:00, nueva fecha hasta el ' . $f->copy()->addDays(45)->locale('es')->isoFormat('D [de] MMMM');
                }
                $pagado = (int) ($r->abono_programa ?? 0);
                $saldo  = (int) ($r->diferencia_programa ?? 0);
                $lineas[] = "- Reserva N°{$r->id}: " . $f->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY')
                    . ", {$r->nombre_programa}, {$r->cantidad_personas} personas, estado: " . ($r->estado ?: 'sin estado')
                    . ($r->abono_programa !== null ? ', abono registrado: $' . number_format($pagado, 0, ',', '.') . ' (pago ' . ($r->estado_pago ?: 'sin estado') . ')' : ', sin pago registrado')
                    . ($r->diferencia_programa !== null ? ', saldo pendiente para el día de la visita: $' . number_format($saldo, 0, ',', '.') : '')
                    . ". ¿Se puede reagendar?: {$reag}.";
            }
            $holds = DB::table('reserva_holds')
                ->whereIn('telefono', array_unique([$tel, $corto]))
                ->whereIn('estado', ['activo', 'pendiente'])
                ->where('expira_en', '>', now())
                ->orderBy('id', 'desc')->limit(2)->get();
            foreach ($holds as $h) {
                $lineas[] = "- Cupo apartado (hold N°{$h->id}) para el {$h->fecha}, {$h->personas} personas: total $" . number_format((int) $h->valor_total, 0, ',', '.')
                    . ', a transferir $' . number_format((int) $h->abono_50, 0, ',', '.') . '. Vence ' . Carbon::parse($h->expira_en)->format('H:i') . ' hrs. Esperando comprobante.';
            }
        } catch (\Throwable $e) {
            Log::error('[Bot] Error armando contexto del cliente: ' . $e->getMessage());
            $lineas[] = '- (No se pudo leer el historial de reservas: si el cliente pregunta por una reserva, deriva a una persona.)';
        }
        $texto = $lineas
            ? implode("\n", $lineas)
            : '- Este WhatsApp no tiene reservas recientes ni futuras en el sistema.';
        return ['texto' => $texto, 'reserva_ids' => $ids];
    }

    /** Montos con "$" en el mensaje que no aparecen en el prompt, el contexto ni los resultados del sistema. */
    private function montosNoVerificados(string $mensaje, string $systemPrompt, array $historial): array
    {
        preg_match_all('/\$\s?(\d{1,3}(?:[.\s]\d{3})+|\d+)/u', $mensaje, $m);
        if (empty($m[1])) {
            return [];
        }
        $fuente = $systemPrompt;
        foreach ($historial as $h) {
            $c = is_string($h['content'] ?? null) ? $h['content'] : '';
            if (strpos($c, '[Sistema') !== false) {
                $fuente .= "\n" . $c;
            }
        }
        preg_match_all('/\d{1,3}(?:\.\d{3})+|\d+/', $fuente, $f);
        $permitidos = [];
        foreach ($f[0] as $n) {
            $permitidos[(int) str_replace('.', '', $n)] = true;
        }
        $malos = [];
        foreach ($m[1] as $n) {
            $v = (int) preg_replace('/\D/', '', $n);
            if ($v > 0 && !isset($permitidos[$v])) {
                $malos[] = $v;
            }
        }
        return array_values(array_unique($malos));
    }

    /**
     * Deriva la conversación a una persona: el bot queda en silencio en ese chat,
     * se registra en la lista de pendientes y se envía una alerta por correo.
     */
    private function escalar(string $tel, string $nombre, string $motivo, string $ultimoMensaje)
    {
        try {
            $horas = \App\Services\BotTarifasService::horasSilencioHumano();
            DB::table('bot_conversaciones')->where('usuario_id', $tel)->where('activo', 1)->update([
                'requiere_humano' => 1,
                'humano_hasta'    => now()->addHours($horas),
                'updated_at'      => now(),
            ]);
            \App\BotMensajePendiente::create([
                'telefono' => $tel, 'nombre' => $nombre, 'tipo' => 'derivado',
                'mensaje'  => 'Derivado por el bot (' . $motivo . '). Último mensaje: ' . $ultimoMensaje,
                'estado'   => 'pendiente',
            ]);
            $correo = \App\BotSeccionTexto::where('clave', 'alerta_correo')->value('contenido');
            if ($correo && \Cache::add('bot_derivado_' . $tel, 1, now()->addMinutes(30))) {
                \Mail::raw("El bot derivó una conversación a una persona.\n\nCliente: {$nombre} (+{$tel})\nMotivo: {$motivo}\nÚltimo mensaje: {$ultimoMensaje}\n\nEl bot no responderá en ese chat por {$horas} horas o hasta que la marques como atendida en " . url('/bot-configuracion') . '.',
                    function ($m) use ($correo, $nombre) {
                        $m->to($correo)->subject('Bot WhatsApp: conversación derivada (' . $nombre . ')');
                    });
            }
        } catch (\Throwable $e) {
            Log::error('[Bot] Error al derivar: ' . $e->getMessage());
        }
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


    // ─────────────────────────────────────────────────────────────
    // GET /api/bot-ai/menu-opciones
    // Retorna productos activos agrupados por tipo (entrada/fondo/acompañamiento)
    // ─────────────────────────────────────────────────────────────
    public function menuOpciones()
    {
        $opciones = $this->cargarMenuOpciones();
        return response()->json(['ok' => true, 'menu' => $opciones]);
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
            $ids = \App\Services\BotTarifasService::idsProgramasBot();
            $semana = \App\Services\BotTarifasService::idsProgramasSemana();
            $programas = Programa::whereIn('id', $ids ?: [0])
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
                    'es_semana'         => in_array((int) $programa->id, $semana, true),
                    'permite_reprogramacion' => (bool) ($programa->permite_reprogramacion ?? true),
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
