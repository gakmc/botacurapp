<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BotReservaController
 *
 * POST /api/bot-ai/reserva
 * Crea cliente, reserva, venta y servicios extra a partir de los datos
 * recopilados por el bot de WhatsApp.
 *
 * Flujo DB:
 * 1. Verificar disponibilidad
 * 2. Find-or-create cliente
 * 3. Lookup servicios en BD (masaje, desayuno-u-once) para obtener IDs y precios reales
 * 4. Calcular total real (programa × personas + masajes extra + desayuno/once)
 * 5. Crear reserva (con cantidad_masajes_extra)
 * 6. Crear Venta (abono=50%, diferencia=50%, tipo_transaccion)
 * 7. Si hay extras: Crear Consumo → DetalleServiciosExtra (masajes y/o desayuno/once)
 *
 * Body JSON:
 * {
 *   "nombre":         "Juan Pérez",
 *   "telefono":       "56912345678",
 *   "email":          "juan@example.com",
 *   "programa_id":     3,
 *   "fecha":          "2026-08-02",
 *   "personas":        2,
 *   "masajes_extra":   1,    (opcional, default 0)
 *   "desayuno_once":   2,    (opcional, default 0 — cantidad personas con Desayuno u Once)
 *   "tipo_pago":      "Débito" (opcional)
 * }
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotReservaController extends Controller
{
    // Slugs de servicios extra en la tabla `servicios`
    const SLUG_MASAJE       = 'masaje';
    const SLUG_DESAYUNO_OCE = 'desayuno-u-once';

    // ID de precios_tipos_masajes para Relajación 30 min (default del bot)
    const MASAJE_PRECIO_TIPO_ID = 1;

    /** ID del usuario sistema. Configurable en .env → BOT_SYSTEM_USER_ID */
    private function getBotUserId()
    {
        return (int) (env('BOT_SYSTEM_USER_ID', 1));
    }

    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        // ── Validar payload ───────────────────────────────────────────────────
        $request->validate([
            'nombre'         => 'required|string|max:200',
            'telefono'       => 'required|string|max:20',
            'email'          => 'required|email|max:200',
            'programa_id'    => 'required|integer|exists:programas,id',
            'fecha'          => 'required|date|after_or_equal:today',
            'personas'       => 'required|integer|min:1|max:50',
            'masajes_extra'  => 'nullable|integer|min:0|max:20',
            'desayuno_once'  => 'nullable|integer|min:0|max:50',
            'desayuno_tipo'  => 'nullable|string|in:desayuno,once',
            'observacion'    => 'nullable|string|max:500',
            'tipo_pago'      => 'nullable|string|max:80',
        ]);

        $telefono      = $this->normalizarTelefono($request->telefono);
        $programaId    = (int) $request->programa_id;
        $fecha         = $request->fecha;
        $personas      = (int) $request->personas;
        $nombre        = trim($request->nombre);
        $email         = strtolower(trim($request->email));
        $masajesExtra  = max(0, (int) ($request->masajes_extra ?? 0));
        $desayunoOnce  = max(0, (int) ($request->desayuno_once ?? 0));
        $desayunoTipo  = ($desayunoOnce > 0 && $request->desayuno_tipo) ? $request->desayuno_tipo : null;
        $observacion   = $request->observacion ? trim($request->observacion) : null;
        $tipoPago      = trim($request->tipo_pago ?? '');

        // ── 1. Verificar disponibilidad ───────────────────────────────────────
        $dispCheck = $this->verificarDisponibilidad($fecha, $programaId, $personas);

        if (!$dispCheck['disponible']) {
            return response()->json([
                'ok'      => false,
                'error'   => 'sin_disponibilidad',
                'motivo'  => $dispCheck['motivo'] ?? 'No hay cupo para esa fecha y programa.',
                'espacio' => $dispCheck['espacio'] ?? null,
                'tinaja'  => $dispCheck['tinaja']  ?? null,
            ], 409);
        }

        // ── 2. Find-or-create cliente ─────────────────────────────────────────
        $clienteId = $this->obtenerOCrearCliente($nombre, $telefono, $email);

        // ── 3. Cargar servicios y precios desde BD ────────────────────────────
        $servicioMasaje = DB::table('servicios')->where('slug', self::SLUG_MASAJE)->first();
        $servicioDyO    = DB::table('servicios')->where('slug', self::SLUG_DESAYUNO_OCE)->first();

        // Precio masaje: usar precios_tipos_masajes (Relajación 30 min) o fallback valor_servicio
        $precioTipoMasaje = DB::table('precios_tipos_masajes')
            ->where('id', self::MASAJE_PRECIO_TIPO_ID)
            ->first();
        $precioMasaje = $precioTipoMasaje
            ? (int) $precioTipoMasaje->precio_unitario
            : ($servicioMasaje ? (int) $servicioMasaje->valor_servicio : 25000);

        // Precio desayuno/once: desde valor_servicio de la tabla servicios
        $precioDyO = $servicioDyO ? (int) $servicioDyO->valor_servicio : 10000;

        // ── 4. Calcular totales reales ────────────────────────────────────────
        $programa          = DB::table('programas')->where('id', $programaId)->first();
        $valorProgramaUnit = $programa ? (int) $programa->valor_programa : 0;
        $totalPrograma     = $valorProgramaUnit * $personas;
        $totalMasajes      = $masajesExtra * $precioMasaje;
        $totalDyO          = $desayunoOnce  * $precioDyO;
        $valorTotal        = $totalPrograma + $totalMasajes + $totalDyO;
        $abono50           = (int) ceil($valorTotal / 2);
        $diferencia        = $valorTotal - $abono50;

        // ── 5. Crear reserva ──────────────────────────────────────────────────
        $notaBot = 'Reserva creada por bot WhatsApp';
        if ($observacion) {
            $notaBot .= " | Ocasión: {$observacion}";
        }

        $reservaId = DB::table('reservas')->insertGetId([
            'cliente_id'             => $clienteId,
            'cantidad_personas'      => $personas,
            'cantidad_masajes'       => 0,             // masajes INCLUIDOS en programa (lo setea el backoffice)
            'cantidad_masajes_extra' => $masajesExtra, // masajes EXTRAS solicitados por el bot
            'fecha_visita'           => $fecha,
            'observacion'            => $notaBot,
            'id_programa'            => $programaId,
            'user_id'                => $this->getBotUserId(),
            'estado'                 => 'pendiente_pago',
            'fuente'                 => 'bot_whatsapp',
            'menu_recibido'          => ($desayunoOnce > 0) ? 1 : 0,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        // ── 6. Buscar tipo de transacción y crear Venta ───────────────────────
        $tipoTransaccionId = $this->buscarTipoTransaccion($tipoPago);

        $ventaId = DB::table('ventas')->insertGetId([
            'id_reserva'                => $reservaId,
            'abono_programa'            => $abono50,
            'folio_abono'               => $tipoPago ?: null,
            'diferencia_programa'       => $diferencia,
            'total_pagar'               => $diferencia,
            'descuento'                 => 0,
            'id_tipo_transaccion_abono' => $tipoTransaccionId,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);

        // ── 7. Crear Consumo + DetalleServiciosExtra (si hay extras) ─────────
        $consumoId = null;
        if (($masajesExtra > 0 && $servicioMasaje) || ($desayunoOnce > 0 && $servicioDyO)) {
            $totalExtras = $totalMasajes + $totalDyO;

            $consumoId = DB::table('consumos')->insertGetId([
                'id_venta'      => $ventaId,
                'subtotal'      => $totalExtras,
                'total_consumo' => $totalExtras,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            // Masajes extra → servicio slug='masaje', precio_tipo_id=1 (Relajación 30 min)
            if ($masajesExtra > 0 && $servicioMasaje) {
                DB::table('detalle_servicios_extra')->insert([
                    'id_consumo'            => $consumoId,
                    'id_servicio_extra'     => $servicioMasaje->id,
                    'cantidad_servicio'     => $masajesExtra,
                    'subtotal'              => $totalMasajes,
                    'id_precio_tipo_masaje' => self::MASAJE_PRECIO_TIPO_ID,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }

            // Desayuno u Once → servicio slug='desayuno-u-once', sin tipo masaje
            if ($desayunoOnce > 0 && $servicioDyO) {
                DB::table('detalle_servicios_extra')->insert([
                    'id_consumo'            => $consumoId,
                    'id_servicio_extra'     => $servicioDyO->id,
                    'cantidad_servicio'     => $desayunoOnce,
                    'subtotal'              => $totalDyO,
                    'id_precio_tipo_masaje' => null,
                    'created_at'            => now(),
                    'updated_at'            => now(),
                ]);
            }
        }

        // ── 8. Crear registros menus (planificación cocina) ──────────────────
        // Un registro por persona que ordenó desayuno u once.
        // Los productos (entrada/fondo/acompañamiento) y alergias se llenan
        // vía bot post-pago o por el backoffice el día de la visita.
        if ($desayunoOnce > 0) {
            for ($i = 0; $i < $desayunoOnce; $i++) {
                DB::table('menus')->insert([
                    'id_reserva'                 => $reservaId,
                    'id_producto_entrada'        => null,
                    'id_producto_fondo'          => null,
                    'id_producto_acompanamiento' => null,
                    'alergias'                   => null,
                    'tipo_servicio'              => $desayunoTipo,
                    'observacion'                => null,
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }
        }

        Log::info('BotReservaController: reserva creada', [
            'reserva_id'     => $reservaId,
            'venta_id'       => $ventaId,
            'consumo_id'     => $consumoId,
            'cliente_id'     => $clienteId,
            'valor_total'    => $valorTotal,
            'total_programa' => $totalPrograma,
            'total_masajes'  => $totalMasajes,
            'total_dyo'      => $totalDyO,
            'abono_50'       => $abono50,
            'diferencia'     => $diferencia,
            'masajes_extra'  => $masajesExtra,
            'desayuno_once'  => $desayunoOnce,
            'precio_masaje'  => $precioMasaje,
            'precio_dyo'     => $precioDyO,
            'tipo_pago'      => $tipoPago,
        ]);

        // Etiqueta legible del tipo de servicio para el mensaje al cliente
        $desayunoTipoLabel = $desayunoTipo === 'desayuno' ? 'Desayuno (10:30–12:00)'
                           : ($desayunoTipo === 'once'    ? 'Once (17:00–18:15)'
                           : 'Desayuno u Once');

        return response()->json([
            'ok'                   => true,
            'reserva_id'           => $reservaId,
            'venta_id'             => $ventaId,
            'consumo_id'           => $consumoId,
            'programa'             => $programa ? $programa->nombre_programa : 'Programa',
            'fecha'                => $fecha,
            'personas'             => $personas,
            'masajes_extra'        => $masajesExtra,
            'precio_masaje'        => $precioMasaje,
            'desayuno_once'        => $desayunoOnce,
            'desayuno_tipo'        => $desayunoTipo,
            'desayuno_tipo_label'  => $desayunoTipoLabel,
            'precio_dyo'           => $precioDyO,
            'observacion'          => $observacion,
            'valor_total'          => $valorTotal,
            'valor_total_formato'  => '$' . number_format($valorTotal, 0, ',', '.'),
            'abono_50'             => $abono50,
            'abono_50_formato'     => '$' . number_format($abono50, 0, ',', '.'),
            'diferencia'           => $diferencia,
            'diferencia_formato'   => '$' . number_format($diferencia, 0, ',', '.'),
            'incluye_menu'         => ($desayunoOnce > 0),
            'mensaje_siguiente'    => "Para confirmar tu reserva N°{$reservaId}, realiza el abono de \$" . number_format($abono50, 0, ',', '.') . ". Envía el comprobante al +56974484112 indicando tu nombre y N° de reserva.",
        ]);
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Verifica disponibilidad reutilizando la misma lógica que DisponibilidadController.
     *
     * @param  string $fecha
     * @param  int    $programaId
     * @param  int    $personas
     * @return array  ['disponible' => bool, 'motivo' => string|null, ...]
     */
    private function verificarDisponibilidad(string $fecha, int $programaId, int $personas)
    {
        $capacidad = [
            'estacion_economico'  => 2,
            'estacion_intermedio' => 2,
            'estacion_full'       => 5,
            'terraza'             => 6,
            'reposera'            => 4,
        ];
        $poolFlexible    = ['terraza', 'reposera', 'wellness'];
        $maxSlotsTinaja  = 16;

        $programa = DB::table('programas')->where('id', $programaId)->first();
        if (!$programa) {
            return ['disponible' => false, 'motivo' => 'Programa no encontrado.'];
        }

        // Slots tinaja
        $reservas    = DB::table('reservas')->where('fecha_visita', $fecha)->pluck('cantidad_personas');
        $slotsUsados = 0;
        foreach ($reservas as $cp) {
            $slotsUsados += ((int) $cp >= 5) ? 2 : 1;
        }
        $slotsNuevos = ($personas >= 5) ? 2 : 1;
        $tinajaOk    = ($slotsUsados + $slotsNuevos) <= $maxSlotsTinaja;

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
                    ->whereIn('p.espacio_tipo', $poolFlexible)
                    ->count();
                $maxPool   = ($capacidad['terraza'] ?? 6) + ($capacidad['reposera'] ?? 4);
                $espacioOk = $usadosPool < $maxPool;
                $espacioInfo = ['tipo' => 'terraza+reposera', 'usados' => $usadosPool, 'max' => $maxPool];
            } else {
                $usados    = DB::table('reservas as r')
                    ->join('programas as p', 'r.id_programa', '=', 'p.id')
                    ->where('r.fecha_visita', $fecha)
                    ->where('p.espacio_tipo', $espacioTipo)
                    ->count();
                $max       = $capacidad[$espacioTipo] ?? 0;
                $espacioOk = $usados < $max;
                $espacioInfo = ['tipo' => $espacioTipo, 'usados' => $usados, 'max' => $max];
            }
        }

        $disponible = $tinajaOk && $espacioOk;
        $motivo     = null;
        if (!$tinajaOk && !$espacioOk) {
            $motivo = 'Sin cupo de tinaja ni de espacio para ese día.';
        } elseif (!$tinajaOk) {
            $motivo = 'Los horarios de tinaja están completos para ese día.';
        } elseif (!$espacioOk) {
            $motivo = 'No hay espacios disponibles para ese programa en ese día.';
        }

        return [
            'disponible' => $disponible,
            'motivo'     => $motivo,
            'tinaja'     => ['slots_usados' => $slotsUsados, 'slots_nuevos' => $slotsNuevos, 'slots_max' => $maxSlotsTinaja],
            'espacio'    => $espacioInfo,
        ];
    }

    /**
     * Busca el cliente por WhatsApp o correo. Si no existe, lo crea.
     *
     * @param  string $nombre
     * @param  string $telefono
     * @param  string $email
     * @return int    cliente_id
     */
    private function obtenerOCrearCliente(string $nombre, string $telefono, string $email)
    {
        // Buscar por WhatsApp primero (más confiable en contexto bot)
        $cliente = DB::table('clientes')->where('whatsapp_cliente', $telefono)->first();

        if (!$cliente) {
            // Intentar por correo
            $cliente = DB::table('clientes')->where('correo', $email)->first();
        }

        if ($cliente) {
            // Actualizar datos que puedan haber cambiado
            DB::table('clientes')->where('id', $cliente->id)->update([
                'nombre_cliente'    => $nombre,
                'whatsapp_cliente'  => $telefono,
                'updated_at'        => now(),
            ]);
            return $cliente->id;
        }

        // Crear nuevo cliente
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

    /**
     * Busca el id de tipos_transacciones más cercano al texto recibido.
     * Usa LIKE case-insensitive. Devuelve null si no encuentra.
     *
     * @param  string $tipoPago  "Débito", "Crédito", "Transferencia", etc.
     * @return int|null
     */
    /**
     * PATCH /api/bot-ai/reserva/{id}/menu-seleccion
     *
     * Guarda la selección de menú de cada persona con IDs reales de productos.
     * La tabla menus tiene un registro por persona (creados al hacer la reserva si
     * desayuno_once > 0). Este endpoint actualiza esos registros con:
     *   id_producto_entrada, id_producto_fondo, id_producto_acompanamiento,
     *   observacion (notas por plato, ej: "sin cebolla"), alergias.
     *
     * Body JSON:
     * {
     *   "selecciones": [
     *     {
     *       "persona":           1,
     *       "entrada_id":        N,
     *       "fondo_id":          N,
     *       "acompanamiento_id": N|null,
     *       "observacion":       "sin cebolla"|null,
     *       "alergias":          "celíaco"|null
     *     }
     *   ]
     * }
     */
    public function guardarSeleccionMenu(Request $request, $id)
    {
        $request->validate([
            'selecciones'                       => 'required|array|min:1',
            'selecciones.*.persona'             => 'required|integer|min:1',
            'selecciones.*.entrada_id'          => 'required|integer|exists:productos,id',
            'selecciones.*.fondo_id'            => 'required|integer|exists:productos,id',
            'selecciones.*.acompanamiento_id'   => 'nullable|integer|exists:productos,id',
            'selecciones.*.observacion'         => 'nullable|string|max:500',
            'selecciones.*.alergias'            => 'nullable|string|max:500',
        ]);

        $reserva = DB::table('reservas')->where('id', $id)->first();
        if (!$reserva) {
            return response()->json(['ok' => false, 'error' => 'Reserva no encontrada'], 404);
        }

        // Obtener registros de menus existentes para esta reserva (ordenados por id)
        $menus = DB::table('menus')
            ->where('id_reserva', $id)
            ->orderBy('id')
            ->get();

        $selecciones = $request->selecciones;
        $guardados   = 0;

        foreach ($selecciones as $i => $sel) {
            $entradaId       = (int) $sel['entrada_id'];
            $fondoId         = (int) $sel['fondo_id'];
            $acompId         = isset($sel['acompanamiento_id']) && $sel['acompanamiento_id']
                                ? (int) $sel['acompanamiento_id']
                                : null;
            $observacion     = isset($sel['observacion']) && $sel['observacion']
                                ? trim($sel['observacion'])
                                : null;
            $alergias        = isset($sel['alergias']) && $sel['alergias']
                                ? trim($sel['alergias'])
                                : null;

            if (isset($menus[$i])) {
                // Actualizar registro existente
                DB::table('menus')->where('id', $menus[$i]->id)->update([
                    'id_producto_entrada'        => $entradaId,
                    'id_producto_fondo'          => $fondoId,
                    'id_producto_acompanamiento' => $acompId,
                    'observacion'                => $observacion,
                    'alergias'                   => $alergias,
                    'updated_at'                 => now(),
                ]);
            } else {
                // Crear nuevo registro si la reserva no tenía suficientes (edge case)
                DB::table('menus')->insert([
                    'id_reserva'                 => $id,
                    'id_producto_entrada'        => $entradaId,
                    'id_producto_fondo'          => $fondoId,
                    'id_producto_acompanamiento' => $acompId,
                    'observacion'                => $observacion,
                    'alergias'                   => $alergias,
                    'tipo_servicio'              => $reserva->tipo_servicio ?? null,
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }
            $guardados++;
        }

        // Marcar reserva como menú recibido
        DB::table('reservas')->where('id', $id)->update([
            'menu_recibido' => 1,
            'updated_at'    => now(),
        ]);

        Log::info("[Bot] Selección de menú guardada para reserva #{$id}", [
            'personas'  => $guardados,
            'selecciones' => $selecciones,
        ]);

        return response()->json([
            'ok'         => true,
            'reserva_id' => (int) $id,
            'guardados'  => $guardados,
            'mensaje'    => '¡Pedido recibido! Tus elecciones de menú quedaron registradas. En los próximos días recibirás los horarios disponibles del spa. 🕐',
        ]);
    }

    private function buscarTipoTransaccion(string $tipoPago)
    {
        if (!$tipoPago) {
            return null;
        }

        // Búsqueda exacta primero
        $tt = DB::table('tipos_transacciones')
            ->whereRaw('LOWER(nombre) = ?', [strtolower($tipoPago)])
            ->first();
        if ($tt) {
            return $tt->id;
        }

        // Búsqueda parcial — mapear variantes comunes
        $claves = [
            'deb'    => 'deb',     // débito, debito, debit
            'cred'   => 'cred',    // crédito, credito, credit
            'transf' => 'transf',  // transferencia
            'efect'  => 'efect',   // efectivo
            'webpay' => 'webpay',
            'web'    => 'web',
        ];

        $lower = strtolower($tipoPago);
        foreach ($claves as $clave => $busqueda) {
            if (strpos($lower, $clave) !== false) {
                $tt = DB::table('tipos_transacciones')
                    ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $busqueda . '%'])
                    ->first();
                if ($tt) {
                    return $tt->id;
                }
            }
        }

        return null;
    }

    /**
     * Normaliza el teléfono a formato numérico sin +.
     * "+56 9 1234 5678" → "56912345678"
     *
     * @param  string $telefono
     * @return string
     */
    private function normalizarTelefono(string $telefono)
    {
        $limpio = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($limpio) === 9 && substr($limpio, 0, 1) === '9') {
            $limpio = '56' . $limpio;
        }
        return $limpio;
    }
}
