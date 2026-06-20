<?php

namespace App\Http\Controllers\Api;

use App\Cliente;
use App\Http\Controllers\Controller;
use App\Programa;
use App\Reserva;
use App\Venta;
use App\TipoTransaccion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/ping
    // Verifica que el endpoint está activo (útil para n8n)
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
    // Devuelve los programas activos con nombre, valor y servicios
    // ─────────────────────────────────────────────────────────────
    public function programas()
    {
        $programas = Programa::activos()
            ->with('servicios:id,nombre_servicio,duracion')
            ->get(['id', 'nombre_programa', 'slug', 'valor_programa', 'descuento'])
            ->map(function ($p) {
                $valor    = $p->valor_programa;
                $descuento = $p->descuento ?? 0;
                return [
                    'id'              => $p->id,
                    'nombre'          => $p->nombre_programa,
                    'slug'            => $p->slug,
                    'valor'           => $valor,
                    'descuento'       => $descuento,
                    'valor_final'     => $valor - $descuento,
                    'valor_formateado'=> '$' . number_format($valor - $descuento, 0, ',', '.'),
                    'servicios'       => $p->servicios->map(function ($s) {
                        return [
                            'nombre'   => $s->nombre_servicio,
                            'duracion' => $s->duracion,
                        ];
                    }),
                ];
            });

        return response()->json(['programas' => $programas]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/disponibilidad?fecha=YYYY-MM-DD[&id_programa=N]
    //
    // Sin id_programa → resumen general de todos los espacios.
    // Con id_programa → verifica disponibilidad del espacio específico
    //   del programa (estacion_economico, estacion_intermedio,
    //   estacion_full, terraza, reposera).
    //
    // Capacidades por espacio:
    //   estacion_economico  → 2 cupos
    //   estacion_intermedio → 2 cupos
    //   estacion_full       → 5 cupos  (compartido entre Relax y Full Day)
    //   terraza             → 5 cupos  (compartido entre Grupal 1 y Grupal 2)
    //   reposera            → 4 cupos  (pares de reposeras)
    // ─────────────────────────────────────────────────────────────
    public function disponibilidad(Request $request)
    {
        $fecha      = $request->query('fecha');
        $idPrograma = $request->query('id_programa');

        if (!$fecha || !$this->esFechaValida($fecha)) {
            return response()->json([
                'error' => 'Fecha inválida. Formato requerido: YYYY-MM-DD',
            ], 422);
        }

        $fechaCarbon = Carbon::parse($fecha);

        // Botacura opera jueves a domingo y festivos
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

        // No permitir fechas pasadas
        if ($fechaCarbon->isPast() && !$fechaCarbon->isToday()) {
            return response()->json([
                'disponible'        => false,
                'fecha'             => $fecha,
                'mensaje'           => 'La fecha ya pasó.',
                'cupos_disponibles' => 0,
                'reservas_actuales' => 0,
            ]);
        }

        $diaFormateado = $fechaCarbon->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $capacidades   = config('app.bot_espacios', [
            'estacion_economico'  => 2,
            'estacion_intermedio' => 2,
            'estacion_full'       => 5,
            'terraza'             => 5,
            'reposera'            => 4,
        ]);

        // ── Consulta con programa específico ───────────────────────
        if ($idPrograma) {
            $programa = DB::table('programas')
                ->where('id', $idPrograma)
                ->select('id', 'nombre_programa', 'espacio_tipo')
                ->first();

            if (!$programa) {
                return response()->json(['error' => 'Programa no encontrado'], 404);
            }

            $espacioTipo = $programa->espacio_tipo;

            if (!$espacioTipo || !isset($capacidades[$espacioTipo])) {
                // Programa sin espacio asignado → usar capacidad legada
                $cuposMax       = config('app.bot_cupos_maximos_dia', 20);
                $reservasActual = Reserva::whereDate('fecha_visita', $fecha)->count();
                $cuposDisp      = max(0, $cuposMax - $reservasActual);

                return response()->json([
                    'disponible'        => $cuposDisp > 0,
                    'fecha'             => $fecha,
                    'dia'               => $diaFormateado,
                    'programa'          => $programa->nombre_programa,
                    'espacio_tipo'      => null,
                    'cupos_maximos'     => $cuposMax,
                    'reservas_actuales' => $reservasActual,
                    'cupos_disponibles' => $cuposDisp,
                    'aviso'             => 'Programa sin espacio_tipo asignado; se usó capacidad global.',
                ]);
            }

            // Contar reservas de la misma fecha para programas del mismo tipo de espacio
            $idsDelMismoTipo = DB::table('programas')
                ->where('espacio_tipo', $espacioTipo)
                ->pluck('id')
                ->toArray();

            $reservasActual = Reserva::whereDate('fecha_visita', $fecha)
                ->whereIn('id_programa', $idsDelMismoTipo)
                ->count();

            $cuposMax  = $capacidades[$espacioTipo];
            $cuposDisp = max(0, $cuposMax - $reservasActual);

            return response()->json([
                'disponible'        => $cuposDisp > 0,
                'fecha'             => $fecha,
                'dia'               => $diaFormateado,
                'programa'          => $programa->nombre_programa,
                'espacio_tipo'      => $espacioTipo,
                'cupos_maximos'     => $cuposMax,
                'reservas_actuales' => $reservasActual,
                'cupos_disponibles' => $cuposDisp,
            ]);
        }

        // ── Resumen general de todos los espacios ──────────────────
        $resumen = [];
        $hayDisponibilidad = false;

        foreach ($capacidades as $tipo => $cuposMax) {
            $idsDelTipo = DB::table('programas')
                ->where('espacio_tipo', $tipo)
                ->pluck('id')
                ->toArray();

            $reservasActual = 0;
            if (!empty($idsDelTipo)) {
                $reservasActual = Reserva::whereDate('fecha_visita', $fecha)
                    ->whereIn('id_programa', $idsDelTipo)
                    ->count();
            }

            $cuposDisp = max(0, $cuposMax - $reservasActual);
            if ($cuposDisp > 0) {
                $hayDisponibilidad = true;
            }

            $resumen[$tipo] = [
                'cupos_maximos'     => $cuposMax,
                'reservas_actuales' => $reservasActual,
                'cupos_disponibles' => $cuposDisp,
                'disponible'        => $cuposDisp > 0,
            ];
        }

        return response()->json([
            'disponible' => $hayDisponibilidad,
            'fecha'      => $fecha,
            'dia'        => $diaFormateado,
            'espacios'   => $resumen,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/clientes/buscar-o-crear
    // Body: { nombre_cliente, whatsapp_cliente, canal, instagram_cliente? }
    // Busca por whatsapp_cliente (campo UNIQUE en clientes)
    // ─────────────────────────────────────────────────────────────
    public function buscarOCrearCliente(Request $request)
    {
        $telefono = $this->normalizarTelefono($request->input('whatsapp_cliente', ''));
        $canal    = $request->input('canal', 'whatsapp');

        if (empty($telefono) && $canal === 'whatsapp') {
            return response()->json(['error' => 'whatsapp_cliente es requerido'], 422);
        }

        $esNuevo = false;

        // Buscar por WhatsApp primero
        $cliente = null;
        if ($telefono) {
            $cliente = Cliente::where('whatsapp_cliente', $telefono)->first();
        }

        // Si viene de Instagram y no hay WA, buscar por instagram_cliente
        if (!$cliente && $canal === 'instagram') {
            $igId = $request->input('instagram_cliente');
            if ($igId) {
                $cliente = Cliente::where('instagram_cliente', $igId)->first();
            }
        }

        if (!$cliente) {
            $nombre = $request->input('nombre_cliente', 'Cliente Bot');
            $correo = $request->input('correo');
            $genero = $request->input('genero');

            $datos = [
                'nombre_cliente'    => $nombre,
                'whatsapp_cliente'  => $telefono ?: null,
                'instagram_cliente' => $request->input('instagram_cliente', ''),
                'sexo'              => $genero ?: '',
                'correo'            => $correo ?: $this->generarCorreoTemporal($telefono, $canal),
            ];

            try {
                $cliente = Cliente::create($datos);
                $esNuevo = true;
                Log::info("[Bot] Cliente creado #{$cliente->id} vía {$canal} — {$nombre}");
            } catch (\Exception $e) {
                Log::error("[Bot] Error creando cliente: " . $e->getMessage());
                return response()->json(['error' => 'No se pudo crear el cliente'], 500);
            }
        } else {
            // Actualizar campos vacíos si ahora tenemos datos
            $actualizaciones = [];
            if (empty($cliente->whatsapp_cliente) && $telefono) {
                $actualizaciones['whatsapp_cliente'] = $telefono;
            }
            if (empty($cliente->instagram_cliente) && $request->filled('instagram_cliente')) {
                $actualizaciones['instagram_cliente'] = $request->input('instagram_cliente');
            }
            if (empty($cliente->correo) && $request->filled('correo')) {
                $actualizaciones['correo'] = $request->input('correo');
            }
            if (empty($cliente->sexo) && $request->filled('genero')) {
                $actualizaciones['sexo'] = $request->input('genero');
            }
            if (empty($cliente->nombre_cliente) && $request->filled('nombre_cliente')) {
                $actualizaciones['nombre_cliente'] = $request->input('nombre_cliente');
            }
            if (!empty($actualizaciones)) {
                $cliente->update($actualizaciones);
            }

            Log::info("[Bot] Cliente encontrado #{$cliente->id} — {$cliente->nombre_cliente}");
        }

        return response()->json([
            'cliente'  => [
                'id'                => $cliente->id,
                'nombre_cliente'    => $cliente->nombre_cliente,
                'whatsapp_cliente'  => $cliente->whatsapp_cliente,
                'instagram_cliente' => $cliente->instagram_cliente,
            ],
            'es_nuevo' => $esNuevo,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/reservas
    // Crea reserva + venta (abono 50%) usando el mismo patrón
    // que ProcesarOrdenWoocommerce
    //
    // Body:
    //   id_cliente, id_programa, cantidad_personas,
    //   fecha_visita (YYYY-MM-DD), canal, observacion?
    // ─────────────────────────────────────────────────────────────
    public function crearReserva(Request $request)
    {
        $idCliente        = $request->input('id_cliente');
        $idPrograma       = $request->input('id_programa');
        $cantidadPersonas = (int) $request->input('cantidad_personas', 1);
        $fechaVisita      = $request->input('fecha_visita');
        $canal            = $request->input('canal', 'whatsapp');
        $observacion      = $request->input('observacion');

        // Validaciones básicas
        if (!$idCliente || !$idPrograma || !$fechaVisita) {
            return response()->json([
                'error' => 'Faltan datos requeridos: id_cliente, id_programa, fecha_visita',
            ], 422);
        }

        if (!$this->esFechaValida($fechaVisita)) {
            return response()->json(['error' => 'Fecha inválida'], 422);
        }

        $programa = Programa::find($idPrograma);
        if (!$programa) {
            return response()->json(['error' => 'Programa no encontrado'], 404);
        }

        $cliente = Cliente::find($idCliente);
        if (!$cliente) {
            return response()->json(['error' => 'Cliente no encontrado'], 404);
        }

        // Calcular montos
        $valorBase    = $programa->valor_programa;
        $descuento    = $programa->descuento ?? 0;
        $valorFinal   = $valorBase - $descuento;
        $totalPagar   = $valorFinal * $cantidadPersonas;
        $abono        = (int) round($totalPagar * 0.5); // 50% de abono

        // ID del usuario bot (configurado en .env como BOT_SYSTEM_USER_ID)
        $botUserId = config('app.bot_system_user_id', 1);

        // Texto de observación enriquecido
        $obsTexto = trim(implode(' | ', array_filter([
            "Reserva vía {$canal}",
            $observacion,
            "Bot-Acura " . now()->format('d-m-Y H:i'),
        ])));

        DB::beginTransaction();
        try {
            // 1) Crear reserva
            $reserva = Reserva::create([
                'cliente_id'        => $cliente->id,
                'id_programa'       => $programa->id,
                'cantidad_personas' => $cantidadPersonas,
                'cantidad_masajes'  => $programa->incluye_masajes ? $cantidadPersonas : null,
                'fecha_visita'      => $fechaVisita,
                'observacion'       => $obsTexto,
                'user_id'           => $botUserId,
                'avisado_en_cocina' => 'reservado',
            ]);

            // 2) Buscar tipo de transacción para transferencia/bot
            // Ajusta el nombre según lo que tengas en tipos_transacciones
            $tipoTransaccion = TipoTransaccion::where('nombre', 'like', '%transferencia%')
                ->orWhere('nombre', 'like', '%depósito%')
                ->orWhere('nombre', 'like', '%deposito%')
                ->first();

            // 3) Crear venta con abono pendiente de confirmación
            $venta = Venta::create([
                'id_reserva'                => $reserva->id,
                'abono_programa'            => null,      // se completa cuando confirmen el pago
                'folio_abono'               => null,      // se completa con comprobante
                'total_pagar'               => $totalPagar,
                'descuento'                 => $descuento * $cantidadPersonas,
                'id_tipo_transaccion_abono' => $tipoTransaccion ? $tipoTransaccion->id : null,
            ]);

            DB::commit();

            Log::info(
                "[Bot] ✓ Reserva #{$reserva->id} creada | " .
                "Cliente #{$cliente->id} ({$cliente->nombre_cliente}) | " .
                "Programa: {$programa->nombre_programa} | " .
                "Fecha: {$fechaVisita} | Canal: {$canal}"
            );

            return response()->json([
                'success'           => true,
                'id_reserva'        => $reserva->id,
                'id_venta'          => $venta->id,
                'cliente'           => $cliente->nombre_cliente,
                'programa'          => $programa->nombre_programa,
                'fecha_visita'      => Carbon::parse($fechaVisita)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
                'cantidad_personas' => $cantidadPersonas,
                'total_pagar'       => $totalPagar,
                'abono_requerido'   => $abono,
                'saldo_dia_visita'  => $totalPagar - $abono,
                'total_formateado'  => '$' . number_format($totalPagar, 0, ',', '.'),
                'abono_formateado'  => '$' . number_format($abono, 0, ',', '.'),
                'saldo_formateado'  => '$' . number_format($totalPagar - $abono, 0, ',', '.'),
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("[Bot] ✗ Error creando reserva: " . $e->getMessage(), [
                'cliente_id' => $idCliente,
                'programa'   => $idPrograma,
                'fecha'      => $fechaVisita,
            ]);
            return response()->json(['error' => 'Error interno al crear la reserva'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/reservas/{id}/pago
    // Registra el abono recibido en la venta asociada a la reserva
    //
    // Body:
    //   monto, folio (número de transferencia o referencia),
    //   tipo_transaccion? (nombre o id)
    // ─────────────────────────────────────────────────────────────
    public function registrarPago(Request $request, $idReserva)
    {
        $reserva = Reserva::with('venta')->find($idReserva);

        if (!$reserva) {
            return response()->json(['error' => 'Reserva no encontrada'], 404);
        }

        $venta = $reserva->venta;
        if (!$venta) {
            return response()->json(['error' => 'La reserva no tiene venta asociada'], 404);
        }

        $monto = (int) $request->input('monto', 0);
        $folio = $request->input('folio', 'BOT-' . now()->format('YmdHis'));

        if ($monto <= 0) {
            return response()->json(['error' => 'El monto debe ser mayor a 0'], 422);
        }

        // Buscar tipo de transacción si se especifica
        $tipoTransaccion = null;
        if ($request->filled('tipo_transaccion')) {
            $tipoTransaccion = TipoTransaccion::where('nombre', 'like', '%' . $request->input('tipo_transaccion') . '%')
                ->first();
        }
        if (!$tipoTransaccion) {
            $tipoTransaccion = TipoTransaccion::where('nombre', 'like', '%transferencia%')
                ->orWhere('nombre', 'like', '%depósito%')
                ->orWhere('nombre', 'like', '%deposito%')
                ->first();
        }

        try {
            $venta->update([
                'abono_programa'            => $monto,
                'folio_abono'               => $folio,
                'id_tipo_transaccion_abono' => $tipoTransaccion ? $tipoTransaccion->id : $venta->id_tipo_transaccion_abono,
            ]);

            Log::info(
                "[Bot] ✓ Pago registrado | Reserva #{$idReserva} | " .
                "Monto: \${$monto} | Folio: {$folio}"
            );

            $saldoPendiente = max(0, ($venta->total_pagar ?? 0) - $monto);

            return response()->json([
                'success'           => true,
                'id_reserva'        => $idReserva,
                'monto_registrado'  => $monto,
                'folio'             => $folio,
                'saldo_pendiente'   => $saldoPendiente,
                'saldo_formateado'  => '$' . number_format($saldoPendiente, 0, ',', '.'),
                'estado'            => $saldoPendiente === 0 ? 'pagado_completo' : 'abono_registrado',
            ]);

        } catch (\Throwable $e) {
            Log::error("[Bot] ✗ Error registrando pago reserva #{$idReserva}: " . $e->getMessage());
            return response()->json(['error' => 'Error al registrar el pago'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/conversacion/{usuario_id}
    // Obtiene el estado actual de la conversación activa
    // ─────────────────────────────────────────────────────────────
    public function getConversacion($usuarioId)
    {
        $conv = DB::table('bot_conversaciones')
            ->where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->orderByDesc('updated_at')
            ->first();

        if (!$conv) {
            return response()->json(['conversacion' => null, 'existe' => false]);
        }

        return response()->json([
            'conversacion' => $conv,
            'existe'       => true,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/conversacion
    // Crea o actualiza el estado de una conversación
    //
    // Body (todos opcionales excepto usuario_id):
    //   usuario_id, canal, paso, nombre_cliente, correo, telefono,
    //   instagram, genero, id_programa, cantidad_personas, fecha_visita,
    //   celebracion_especial, tipo_pago, incluye_masajes, incluye_menu,
    //   politicas_aceptadas, id_cliente, id_reserva, ultimo_mensaje,
    //   activo, motivo_cierre
    // ─────────────────────────────────────────────────────────────
    public function upsertConversacion(Request $request)
    {
        $usuarioId = $request->input('usuario_id');

        if (!$usuarioId) {
            return response()->json(['error' => 'usuario_id es requerido'], 422);
        }

        $existe = DB::table('bot_conversaciones')
            ->where('usuario_id', $usuarioId)
            ->where('activo', 1)
            ->exists();

        $campos = array_filter([
            'paso'                => $request->input('paso'),
            'nombre_cliente'      => $request->input('nombre_cliente'),
            'correo'              => $request->input('correo'),
            'telefono'            => $request->input('telefono'),
            'instagram'           => $request->input('instagram'),
            'genero'              => $request->input('genero'),
            'id_programa'         => $request->input('id_programa'),
            'cantidad_personas'   => $request->input('cantidad_personas'),
            'fecha_visita'        => $request->input('fecha_visita'),
            'celebracion_especial'=> $request->input('celebracion_especial'),
            'tipo_pago'           => $request->input('tipo_pago'),
            'incluye_masajes'     => $request->input('incluye_masajes'),
            'incluye_menu'        => $request->input('incluye_menu'),
            'politicas_aceptadas' => $request->input('politicas_aceptadas'),
            'id_cliente'          => $request->input('id_cliente'),
            'id_reserva'          => $request->input('id_reserva'),
            'ultimo_mensaje'      => $request->input('ultimo_mensaje'),
            'activo'              => $request->input('activo'),
            'motivo_cierre'       => $request->input('motivo_cierre'),
        ], function ($v) { return !is_null($v); });

        if ($existe) {
            DB::table('bot_conversaciones')
                ->where('usuario_id', $usuarioId)
                ->where('activo', 1)
                ->update(array_merge($campos, ['updated_at' => now()]));

            $accion = 'actualizada';
        } else {
            DB::table('bot_conversaciones')->insert(array_merge(
                [
                    'usuario_id' => $usuarioId,
                    'canal'      => $request->input('canal', 'whatsapp'),
                    'paso'       => 0,
                    'activo'     => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                $campos
            ));

            $accion = 'creada';
        }

        return response()->json(['success' => true, 'accion' => $accion]);
    }

    // ─────────────────────────────────────────────────────────────
    // GET /api/bot/productos?categoria=entrada|fondo|acompanamiento
    // Devuelve productos activos filtrados por tipo/categoría
    // ─────────────────────────────────────────────────────────────
    public function productos(Request $request)
    {
        $categoria = $request->query('categoria'); // entrada|fondo|acompanamiento

        $query = \App\Producto::with('tipoProducto')->activos();

        if ($categoria) {
            $query->whereHas('tipoProducto', function ($q) use ($categoria) {
                $q->where('nombre', 'like', '%' . $categoria . '%');
            });
        }

        $productos = $query->get(['id', 'nombre', 'valor', 'id_tipo_producto'])->map(function ($p) {
            return [
                'id'        => $p->id,
                'nombre'    => $p->nombre,
                'valor'     => $p->valor,
                'categoria' => $p->tipoProducto ? $p->tipoProducto->nombre : null,
            ];
        });

        return response()->json(['productos' => $productos]);
    }

    // ─────────────────────────────────────────────────────────────
    // POST /api/bot/menu
    // Crea o actualiza el menú de una reserva
    //
    // Body:
    //   id_reserva, id_producto_entrada?, id_producto_fondo?,
    //   id_producto_acompanamiento?, alergias?, observacion?
    // ─────────────────────────────────────────────────────────────
    public function guardarMenu(Request $request)
    {
        $idReserva = $request->input('id_reserva');

        if (!$idReserva) {
            return response()->json(['error' => 'id_reserva es requerido'], 422);
        }

        $reserva = Reserva::find($idReserva);
        if (!$reserva) {
            return response()->json(['error' => 'Reserva no encontrada'], 404);
        }

        $campos = array_filter([
            'id_producto_entrada'        => $request->input('id_producto_entrada'),
            'id_producto_fondo'          => $request->input('id_producto_fondo'),
            'id_producto_acompanamiento' => $request->input('id_producto_acompanamiento'),
            'alergias'                   => $request->input('alergias'),
            'observacion'                => $request->input('observacion'),
        ], function ($v) { return !is_null($v); });

        $menu = \App\Menu::where('id_reserva', $idReserva)->first();

        if ($menu) {
            $menu->update($campos);
            $accion = 'actualizado';
        } else {
            $menu = \App\Menu::create(array_merge(['id_reserva' => $idReserva], $campos));
            $accion = 'creado';
        }

        Log::info("[Bot] Menú #{$menu->id} {$accion} para reserva #{$idReserva}");

        return response()->json([
            'success'    => true,
            'accion'     => $accion,
            'id_menu'    => $menu->id,
            'id_reserva' => $idReserva,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS PRIVADOS
    // ─────────────────────────────────────────────────────────────

    /**
     * Normaliza un número de teléfono al formato 569XXXXXXXX
     * (mismo criterio que Cliente::store())
     */
    private function normalizarTelefono(string $telefono): string
    {
        // Eliminar todo excepto dígitos
        $limpio = preg_replace('/[^0-9]/', '', $telefono);

        if (empty($limpio)) {
            return '';
        }

        // Si ya empieza con 56, dejarlo
        if (substr($limpio, 0, 2) === '56') {
            return $limpio;
        }

        // Si empieza con 9 (móvil chileno sin código país)
        if (substr($limpio, 0, 1) === '9') {
            return '56' . $limpio;
        }

        return '56' . $limpio;
    }

    /**
     * Genera un correo temporal único para clientes que llegan
     * por WhatsApp/Instagram sin correo registrado.
     * El correo puede actualizarse después desde el backoffice.
     */
    private function generarCorreoTemporal(string $telefono, string $canal): string
    {
        $base = $telefono ?: uniqid();
        return "bot_{$canal}_{$base}@botacura.cl";
    }

    /**
     * Valida que una cadena sea una fecha en formato YYYY-MM-DD
     */
    private function esFechaValida(string $fecha): bool
    {
        try {
            $dt = \DateTime::createFromFormat('Y-m-d', $fecha);
            return $dt && $dt->format('Y-m-d') === $fecha;
        } catch (\Exception $e) {
            return false;
        }
    }
}
