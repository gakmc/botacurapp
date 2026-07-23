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
 * Crea cliente, reserva, venta y registros de menú a partir de los datos
 * recopilados por el bot de WhatsApp.
 *
 * Flujo:
 * 1. Verificar disponibilidad
 * 2. Find-or-create cliente
 * 3. Calcular total real (programa × personas + masajes × $25.000 + menú × $10.000)
 * 4. Crear reserva (con cantidad_masajes)
 * 5. Crear Venta (abono=50%, diferencia=50%, tipo_transaccion)
 * 6. Crear registros menus (si aplica)
 *
 * Body JSON:
 * {
 *   "nombre":        "Juan Pérez",
 *   "telefono":      "56912345678",
 *   "email":         "juan@example.com",
 *   "programa_id":    3,
 *   "fecha":         "2026-08-02",
 *   "personas":       2,
 *   "masajes_extra":  1,        (opcional, default 0)
 *   "menu_personas":  2,        (opcional, default 0)
 *   "menu_tipo":     "desayuno", (opcional: desayuno|once)
 *   "tipo_pago":     "Débito"   (opcional)
 * }
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotReservaController extends Controller
{
    const PRECIO_MASAJE_EXTRA = 25000; // Masaje relajación 30 min
    const PRECIO_MENU         = 10000; // Desayuno u once por persona

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
            'nombre'        => 'required|string|max:200',
            'telefono'      => 'required|string|max:20',
            'email'         => 'required|email|max:200',
            'programa_id'   => 'required|integer|exists:programas,id',
            'fecha'         => 'required|date|after_or_equal:today',
            'personas'      => 'required|integer|min:1|max:50',
            'masajes_extra' => 'nullable|integer|min:0|max:20',
            'menu_personas' => 'nullable|integer|min:0|max:50',
            'menu_tipo'     => 'nullable|string|in:desayuno,once',
            'tipo_pago'     => 'nullable|string|max:80',
        ]);

        $telefono     = $this->normalizarTelefono($request->telefono);
        $programaId   = (int) $request->programa_id;
        $fecha        = $request->fecha;
        $personas     = (int) $request->personas;
        $nombre       = trim($request->nombre);
        $email        = strtolower(trim($request->email));
        $masajesExtra = max(0, (int) ($request->masajes_extra ?? 0));
        $menuPersonas = max(0, (int) ($request->menu_personas ?? 0));
        $menuTipo     = $request->menu_tipo;
        $tipoPago     = trim($request->tipo_pago ?? '');

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

        // ── 3. Calcular totales reales ────────────────────────────────────────
        $programa            = DB::table('programas')->where('id', $programaId)->first();
        $valorProgramaUnit   = $programa ? (int) $programa->valor_programa : 0;
        $totalPrograma       = $valorProgramaUnit * $personas;
        $totalMasajes        = $masajesExtra * self::PRECIO_MASAJE_EXTRA;
        $totalMenu           = $menuPersonas  * self::PRECIO_MENU;
        $valorTotal          = $totalPrograma + $totalMasajes + $totalMenu;
        $abono50             = (int) ceil($valorTotal / 2);
        $diferencia          = $valorTotal - $abono50;

        // ── 4. Crear reserva ──────────────────────────────────────────────────
        $reservaId = DB::table('reservas')->insertGetId([
            'cliente_id'        => $clienteId,
            'cantidad_personas' => $personas,
            'cantidad_masajes'  => $masajesExtra,
            'fecha_visita'      => $fecha,
            'observacion'       => 'Reserva creada por bot WhatsApp',
            'id_programa'       => $programaId,
            'user_id'           => $this->getBotUserId(),
            'estado'            => 'pendiente_pago',
            'fuente'            => 'bot_whatsapp',
            'menu_recibido'     => ($menuPersonas > 0 && $menuTipo) ? 1 : 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        // ── 5. Buscar tipo de transacción ─────────────────────────────────────
        $tipoTransaccionId = $this->buscarTipoTransaccion($tipoPago);

        // ── 6. Crear Venta ────────────────────────────────────────────────────
        $ventaId = DB::table('ventas')->insertGetId([
            'id_reserva'                 => $reservaId,
            'abono_programa'             => $abono50,
            'folio_abono'                => $tipoPago ?: null,
            'diferencia_programa'        => $diferencia,
            'total_pagar'                => $diferencia,
            'descuento'                  => 0,
            'id_tipo_transaccion_abono'  => $tipoTransaccionId,
            'created_at'                 => now(),
            'updated_at'                 => now(),
        ]);

        // ── 7. Crear registros de menú ────────────────────────────────────────
        if ($menuPersonas > 0 && $menuTipo) {
            for ($i = 0; $i < $menuPersonas; $i++) {
                DB::table('menus')->insert([
                    'id_reserva'   => $reservaId,
                    'tipo_servicio' => $menuTipo,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }

        Log::info('BotReservaController: reserva creada', [
            'reserva_id'    => $reservaId,
            'venta_id'      => $ventaId,
            'cliente_id'    => $clienteId,
            'valor_total'   => $valorTotal,
            'abono_50'      => $abono50,
            'diferencia'    => $diferencia,
            'masajes_extra' => $masajesExtra,
            'menu_personas' => $menuPersonas,
            'menu_tipo'     => $menuTipo,
            'tipo_pago'     => $tipoPago,
        ]);

        return response()->json([
            'ok'                  => true,
            'reserva_id'          => $reservaId,
            'venta_id'            => $ventaId,
            'programa'            => $programa ? $programa->nombre_programa : 'Programa',
            'fecha'               => $fecha,
            'personas'            => $personas,
            'masajes_extra'       => $masajesExtra,
            'menu_personas'       => $menuPersonas,
            'menu_tipo'           => $menuTipo,
            'valor_total'         => $valorTotal,
            'valor_total_formato' => '$' . number_format($valorTotal, 0, ',', '.'),
            'abono_50'            => $abono50,
            'abono_50_formato'    => '$' . number_format($abono50, 0, ',', '.'),
            'diferencia'          => $diferencia,
            'diferencia_formato'  => '$' . number_format($diferencia, 0, ',', '.'),
            'instrucciones_pago'  => [
                'transferencia' => [
                    'abono'                => '50% al reservar: $' . number_format($abono50, 0, ',', '.'),
                    'saldo'                => '50% el día de visita: $' . number_format($diferencia, 0, ',', '.'),
                    'enviar_comprobante_a' => '+56974484112 o hola@botacura.cl',
                ],
            ],
            'mensaje_siguiente' => "Para confirmar tu reserva N°{$reservaId}, transfiere el abono de $" . number_format($abono50, 0, ',', '.') . " e indica tu nombre y N° de reserva al +56974484112 o hola@botacura.cl.",
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
