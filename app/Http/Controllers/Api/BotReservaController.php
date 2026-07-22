<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FintocService;
use App\Services\WebpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BotReservaController
 *
 * POST /api/bot/reserva
 * Crea el conjunto completo de registros que genera una reserva manual:
 *   reservas + ventas + visitas + masajes + menus
 *
 * Body JSON:
 * {
 *   "nombre":        "Juan Pérez",
 *   "telefono":      "56912345678",
 *   "email":         "juan@example.com",
 *   "programa_id":   3,
 *   "fecha":         "2026-08-02",
 *   "personas":      2,
 *   "masajes_extra": 0,   (opcional)
 *   "menus_extra":   0    (opcional)
 * }
 */
class BotReservaController extends Controller
{
    // 'pendiente' = estado legacy de producción; 'pendiente_pago' = bot WhatsApp
    private $estadosOcupados = ['pendiente', 'pendiente_pago', 'pago_parcial', 'pagado', 'confirmado'];

    private function getBotUserId(): int
    {
        return (int) env('BOT_SYSTEM_USER_ID', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'nombre'         => 'required|string|max:200',
            'telefono'       => 'required|string|max:20',
            'email'          => 'required|email|max:200',
            'programa_id'    => 'required|integer|exists:programas,id',
            'fecha'          => 'required|date|after_or_equal:today',
            'personas'       => 'required|integer|min:1|max:50',
            'masajes_extra'       => 'nullable|integer|min:0|max:10',
            'menus_extra'         => 'nullable|integer|min:0|max:20',
            'tipo_servicio'       => 'nullable|string|in:desayuno,once,desayuno_y_once',
            'alimentacion_extra'  => 'nullable|boolean',
            'almuerzo_extra'      => 'nullable|boolean',
            'estacion_extra'      => 'nullable|boolean',
            'sauna_extra'         => 'nullable|boolean',
            'tinaja_extra'        => 'nullable|boolean',
        ]);

        $telefono     = $this->normalizarTelefono($request->telefono);
        $programaId   = (int) $request->programa_id;
        $fecha        = $request->fecha;
        $personas     = (int) $request->personas;
        $nombre       = trim($request->nombre);
        $email        = strtolower(trim($request->email));
        $masajesExtra       = (int)  ($request->masajes_extra     ?? 0);
        $menusExtra         = (int)  ($request->menus_extra       ?? 0);
        $tipoServicio       =         $request->tipo_servicio      ?? null;
        $alimentacionExtra  = (bool) ($request->alimentacion_extra ?? false);
        $almuerzoExtra      = (bool) ($request->almuerzo_extra     ?? false);
        $estacionExtra      = (bool) ($request->estacion_extra     ?? false);
        $saunaExtra         = (bool) ($request->sauna_extra        ?? false);
        $tinajaExtra        = (bool) ($request->tinaja_extra       ?? false);
        $botUserId          = $this->getBotUserId();

        // ── 1. Verificar disponibilidad ───────────────────────────────────────
        $dispCheck = $this->verificarDisponibilidad($fecha, $programaId, $personas);

        if (!$dispCheck['disponible']) {
            return response()->json([
                'ok'     => false,
                'error'  => 'sin_disponibilidad',
                'motivo' => $dispCheck['motivo'] ?? 'No hay cupo para esa fecha y programa.',
                'tinaja' => $dispCheck['tinaja'] ?? null,
                'espacio'=> $dispCheck['espacio'] ?? null,
            ], 409);
        }

        // ── 2. Cargar programa ────────────────────────────────────────────────
        $programa = \App\Programa::with('servicios')->findOrFail($programaId);
        $valorTotal    = (int) $programa->valor_programa * $personas;
        $incluyeMasaje = $programa->incluye_masajes;
        $incluyeMenu   = $programa->incluye_almuerzos;

        // Extras (precios desde servicios BD)
        if ($masajesExtra > 0)                   { $valorTotal += $masajesExtra * 25000; } // $25.000/persona
        if ($almuerzoExtra)                      { $valorTotal += $personas * 23800; }     // $23.800/persona
        if ($alimentacionExtra && $tipoServicio) { $valorTotal += $personas * 10000; }     // $10.000/persona
        if ($estacionExtra)                      { $valorTotal += 20000; }                 // $20.000 plano grupo
        if ($saunaExtra)                         { $valorTotal += $personas * 7500; }      // $7.500/persona
        if ($tinajaExtra)                        { $valorTotal += $personas * 11000; }     // $11.000/persona

        // ── 3. Find-or-create cliente (no compromete disponibilidad) ─────────
        $clienteId = $this->obtenerOCrearCliente($nombre, $telefono, $email);

        $appUrl = rtrim(env('APP_URL', 'http://localhost'), '/');

        // ── 4. Iniciar Webpay y guardar datos en webpay_pendientes ────────────
        //      NO se crea reserva/venta/visita — eso ocurre al confirmar el pago.
        $webpayUrl   = null;
        $webpayError = null;

        try {
            $webpay    = new WebpayService();
            $buyOrder  = 'BTC-' . time() . '-' . substr(md5($telefono), 0, 4);
            $sessionId = 'bot-' . $clienteId . '-' . date('His');
            $returnUrl = $appUrl . '/pago/webpay/retorno';

            $wpResult = $webpay->initTransaction($valorTotal, $buyOrder, $sessionId, $returnUrl);

            if ($wpResult['ok']) {
                $webpayUrl = $wpResult['url'] . '?token_ws=' . $wpResult['token'];

                // Guardar todos los datos necesarios para crear la reserva post-pago
                DB::table('webpay_pendientes')->insert([
                    'webpay_token' => $wpResult['token'],
                    'webpay_orden' => $buyOrder,
                    'monto'        => $valorTotal,
                    'datos_json'   => json_encode([
                        'cliente_id'    => $clienteId,
                        'nombre'        => $nombre,
                        'telefono'      => $telefono,
                        'email'         => $email,
                        'programa_id'   => $programaId,
                        'fecha'         => $fecha,
                        'personas'      => $personas,
                        'masajes_extra'      => $masajesExtra,
                        'menus_extra'        => $menusExtra,
                        'tipo_servicio'      => $tipoServicio,
                        'alimentacion_extra' => $alimentacionExtra,
                        'almuerzo_extra'     => $almuerzoExtra,
                        'estacion_extra'     => $estacionExtra,
                        'sauna_extra'        => $saunaExtra,
                        'tinaja_extra'       => $tinajaExtra,
                        'incluye_masaje'     => $incluyeMasaje,
                        'incluye_menu'  => $incluyeMenu,
                        'bot_user_id'   => $botUserId,
                    ]),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                Log::info('[BotReserva] Webpay iniciado (sin reserva aún)', [
                    'token'    => $wpResult['token'],
                    'cliente'  => $nombre,
                    'programa' => $programa->nombre_programa,
                    'fecha'    => $fecha,
                    'monto'    => $valorTotal,
                ]);
            } else {
                $webpayError = $wpResult['error'] ?? 'Error Webpay';
                Log::error('[BotReserva] Webpay initTransaction falló', ['error' => $webpayError]);
            }
        } catch (\Exception $e) {
            $webpayError = $e->getMessage();
            Log::error('[BotReserva] Excepción Webpay: ' . $e->getMessage());
        }

        if (!$webpayUrl) {
            return response()->json([
                'ok'           => false,
                'error'        => 'No se pudo generar el link de pago. Inténtalo de nuevo.',
                'webpay_error' => $webpayError,
            ], 500);
        }

        $ppp = $personas > 0 ? (int) ($valorTotal / $personas) : $valorTotal;

        return response()->json([
            'ok'                  => true,
            'reserva_id'          => null, // Se asigna recién al confirmar el pago
            'programa'            => $programa->nombre_programa,
            'fecha'               => $fecha,
            'personas'            => $personas,
            'incluye_masaje'      => $incluyeMasaje,
            'incluye_menu'        => $incluyeMenu,
            'masajes_extra'       => $masajesExtra,
            'menus_extra'         => $menusExtra,
            'valor_total'         => $valorTotal,
            'valor_total_formato' => '$' . number_format($valorTotal, 0, ',', '.'),
            'precio_por_persona'  => '$' . number_format($ppp, 0, ',', '.'),
            'enlace_pago'         => $webpayUrl,
            'webpay_ok'           => true,
            'webpay_error'        => null,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OPCIONES DE MENÚ
    // GET /api/bot/menu-opciones
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna entradas, fondos y acompañamientos activos desde productos.
     * El bot los inyecta al cliente para que elija.
     */
    public function menuOpciones()
    {
        try {
            $productos = DB::table('productos as p')
                ->join('tipos_productos as t', 't.id', '=', 'p.id_tipo_producto')
                ->where('p.estado', 'activo')
                ->select('p.id', 'p.nombre', 't.nombre as tipo')
                ->orderBy('t.nombre')
                ->orderBy('p.nombre')
                ->get();

            $entradas        = [];
            $fondos          = [];
            $acompañamientos = [];

            foreach ($productos as $p) {
                $item = ['id' => $p->id, 'nombre' => $p->nombre];
                switch (strtolower($p->tipo)) {
                    case 'entrada':        $entradas[]        = $item; break;
                    case 'fondo':          $fondos[]          = $item; break;
                    case 'acompañamiento': $acompañamientos[] = $item; break;
                }
            }

            return response()->json([
                'ok'              => true,
                'entradas'        => $entradas,
                'fondos'          => $fondos,
                'acompañamientos' => $acompañamientos,
            ]);
        } catch (\Exception $e) {
            Log::error('[BotReserva] menuOpciones error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GUARDAR ELECCIONES DE MENÚ
    // PATCH /api/bot/reserva/{id}/menu
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Guarda las elecciones de menú por persona.
     *
     * Body: {
     *   "todos_igual": true,                   // si todos comen lo mismo
     *   "entrada_id": 5,                       // usado si todos_igual=true
     *   "fondo_id": 12,
     *   "acompanamiento_id": 3,                // nullable
     *   "alergias": "sin gluten",              // nullable, aplica a todos
     *   "menus": [                             // usado si todos_igual=false
     *     { "entrada_id": 5, "fondo_id": 12, "acompanamiento_id": 3, "alergias": "" },
     *     { "entrada_id": 6, "fondo_id": 11, "acompanamiento_id": null, "alergias": "" }
     *   ]
     * }
     */
    public function updateMenu(Request $request, int $reservaId)
    {
        $reserva = DB::table('reservas')->where('id', $reservaId)->first();
        if (!$reserva) {
            return response()->json(['ok' => false, 'error' => 'Reserva no encontrada'], 404);
        }

        $menus = DB::table('menus')->where('id_reserva', $reservaId)->orderBy('id')->get();
        if ($menus->isEmpty()) {
            return response()->json(['ok' => false, 'error' => 'No hay registros de menú para esta reserva'], 422);
        }

        $todosIgual = (bool) ($request->input('todos_igual', false));

        if ($todosIgual) {
            $request->validate([
                'entrada_id'       => 'required|integer|exists:productos,id',
                'fondo_id'         => 'required|integer|exists:productos,id',
                'acompanamiento_id'=> 'nullable|integer|exists:productos,id',
                'alergias'         => 'nullable|string|max:500',
            ]);
            // Aplicar misma elección a todos los menús
            DB::table('menus')
                ->where('id_reserva', $reservaId)
                ->update([
                    'id_producto_entrada'         => $request->input('entrada_id'),
                    'id_producto_fondo'           => $request->input('fondo_id'),
                    'id_producto_acompanamiento'  => $request->input('acompanamiento_id'),
                    'alergias'                    => $request->input('alergias'),
                    'updated_at'                  => now(),
                ]);
        } else {
            // Elecciones por persona
            $choices = $request->input('menus', []);
            foreach ($menus as $i => $menu) {
                if (!isset($choices[$i])) {
                    continue;
                }
                $c = $choices[$i];
                DB::table('menus')->where('id', $menu->id)->update([
                    'id_producto_entrada'        => $c['entrada_id']        ?? null,
                    'id_producto_fondo'          => $c['fondo_id']          ?? null,
                    'id_producto_acompanamiento' => $c['acompanamiento_id'] ?? null,
                    'alergias'                   => $c['alergias']          ?? null,
                    'updated_at'                 => now(),
                ]);
            }
        }

        // Marcar reserva como menú recibido
        DB::table('reservas')->where('id', $reservaId)->update([
            'menu_recibido' => 1,
            'updated_at'    => now(),
        ]);

        Log::info('[BotReserva] Menú actualizado post-pago', [
            'reserva_id'  => $reservaId,
            'todos_igual' => $todosIgual,
        ]);

        return response()->json([
            'ok'         => true,
            'reserva_id' => $reservaId,
            'mensaje'    => 'Menú guardado correctamente',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ACTUALIZAR TIPO_SERVICIO (post-pago, respuesta del cliente)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * PATCH /api/bot/reserva/{id}/tipo-servicio
     * Actualiza tipo_servicio en todos los menús de la reserva.
     * El bot llama esto cuando el cliente responde "Desayuno" u "Once" post-pago.
     */
    public function updateTipoServicio(Request $request, int $reservaId)
    {
        $request->validate([
            'tipo_servicio' => 'required|string|in:desayuno,once,desayuno_y_once',
        ]);

        $reserva = DB::table('reservas')->where('id', $reservaId)->first();
        if (!$reserva) {
            return response()->json(['ok' => false, 'error' => 'Reserva no encontrada'], 404);
        }

        $updated = DB::table('menus')
            ->where('id_reserva', $reservaId)
            ->update([
                'tipo_servicio' => $request->tipo_servicio,
                'updated_at'    => now(),
            ]);

        Log::info('[BotReserva] tipo_servicio actualizado post-pago', [
            'reserva_id'    => $reservaId,
            'tipo_servicio' => $request->tipo_servicio,
            'menus_updated' => $updated,
        ]);

        return response()->json([
            'ok'            => true,
            'reserva_id'    => $reservaId,
            'tipo_servicio' => $request->tipo_servicio,
            'menus_updated' => $updated,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function verificarDisponibilidad(string $fecha, int $programaId, int $personas): array
    {
        $capacidad = [
            'estacion_economico'  => 2,
            'estacion_intermedio' => 2,
            'estacion_full'       => 5,
            'terraza'             => 6,
            'reposera'            => 4,
        ];
        $poolFlexible   = ['terraza', 'reposera', 'wellness']; // wellness comparte pool terraza+reposera
        $maxSlotsTinaja = 16;

        $programa = DB::table('programas')->where('id', $programaId)->first();
        if (!$programa) {
            return ['disponible' => false, 'motivo' => 'Programa no encontrado.'];
        }

        // Tinaja — solo reservas activas
        $reservas    = DB::table('reservas')
            ->where('fecha_visita', $fecha)
            ->whereIn('estado', $this->estadosOcupados)
            ->pluck('cantidad_personas');
        $slotsUsados = 0;
        foreach ($reservas as $cp) {
            $slotsUsados += ((int) $cp >= 5) ? 2 : 1;
        }
        $slotsNuevos = ($personas >= 5) ? 2 : 1;
        $tinajaOk    = ($slotsUsados + $slotsNuevos) <= $maxSlotsTinaja;

        if (!$tinajaOk) {
            return [
                'disponible' => false,
                'motivo'     => 'Los horarios de tinaja están completos para ese día.',
                'tinaja'     => ['slots_usados' => $slotsUsados, 'slots_max' => $maxSlotsTinaja],
                'espacio'    => [],
            ];
        }

        // Espacio
        $espacioTipo = $programa->espacio_tipo ?? null;
        $espacioOk   = true;
        $espacioInfo = [];

        if ($espacioTipo) {
            $esFlexible = in_array($espacioTipo, $poolFlexible);
            if ($esFlexible) {
                $usadosPool = DB::table('reservas as r')
                    ->join('programas as p', 'r.id_programa', '=', 'p.id')
                    ->where('r.fecha_visita', $fecha)
                    ->whereIn('r.estado', $this->estadosOcupados)
                    ->whereIn('p.espacio_tipo', $poolFlexible)
                    ->count();
                $maxPool    = $capacidad['terraza'] + $capacidad['reposera'];
                $espacioOk  = $usadosPool < $maxPool;
                $espacioInfo = ['tipo' => 'terraza+reposera', 'usados' => $usadosPool, 'max' => $maxPool];
            } else {
                $usados    = DB::table('reservas as r')
                    ->join('programas as p', 'r.id_programa', '=', 'p.id')
                    ->where('r.fecha_visita', $fecha)
                    ->whereIn('r.estado', $this->estadosOcupados)
                    ->where('p.espacio_tipo', $espacioTipo)
                    ->count();
                $max       = $capacidad[$espacioTipo] ?? 0;
                $espacioOk = $usados < $max;
                $espacioInfo = ['tipo' => $espacioTipo, 'usados' => $usados, 'max' => $max];
            }
        }

        $disponible = $tinajaOk && $espacioOk;
        $motivo     = null;
        if (!$espacioOk) {
            $motivo = 'No hay espacios disponibles para ese programa en ese día.';
        }

        return [
            'disponible' => $disponible,
            'motivo'     => $motivo,
            'tinaja'     => ['slots_usados' => $slotsUsados, 'slots_nuevos' => $slotsNuevos, 'slots_max' => $maxSlotsTinaja],
            'espacio'    => $espacioInfo,
        ];
    }

    private function obtenerOCrearCliente(string $nombre, string $telefono, string $email): int
    {
        $cliente = DB::table('clientes')->where('whatsapp_cliente', $telefono)->first()
                ?? DB::table('clientes')->where('correo', $email)->first();

        if ($cliente) {
            DB::table('clientes')->where('id', $cliente->id)->update([
                'nombre_cliente'   => $nombre,
                'whatsapp_cliente' => $telefono,
                'correo'           => $email,
                'updated_at'       => now(),
            ]);
            return $cliente->id;
        }

        return DB::table('clientes')->insertGetId([
            'nombre_cliente'    => $nombre,
            'whatsapp_cliente'  => $telefono,
            'instagram_cliente' => null,
            'sexo'              => null,
            'correo'            => $email,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    private function normalizarTelefono(string $telefono): string
    {
        $limpio = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($limpio) === 9 && $limpio[0] === '9') {
            $limpio = '56' . $limpio;
        }
        return $limpio;
    }
}
