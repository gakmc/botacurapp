<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * BotReservaController
 *
 * POST /api/bot/reserva
 * Crea (o actualiza) un cliente y una reserva a partir de los datos
 * recopilados por el bot de WhatsApp.
 *
 * Flujo:
 * 1. Verificar disponibilidad (reutiliza lógica de DisponibilidadController)
 * 2. Find-or-create cliente por whatsapp_cliente o correo
 * 3. Crear reserva con estado='pendiente_pago', fuente='bot_whatsapp'
 * 4. Retornar ID de reserva + instrucciones de pago
 *
 * Body JSON:
 * {
 *   "nombre":     "Juan Pérez",
 *   "telefono":   "56912345678",
 *   "email":      "juan@example.com",
 *   "programa_id": 3,
 *   "fecha":      "2026-08-02",
 *   "personas":    2
 * }
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotReservaController extends Controller
{
    /** ID del usuario sistema (creado por BotUserSeeder). Configurable en .env */
    private function getBotUserId()
    {
        return (int) (env('BOT_SYSTEM_USER_ID', 1));
    }

    // -------------------------------------------------------------------------

    public function store(Request $request)
    {
        // ── Validar payload ───────────────────────────────────────────────────
        $request->validate([
            'nombre'      => 'required|string|max:200',
            'telefono'    => 'required|string|max:20',
            'email'       => 'required|email|max:200',
            'programa_id' => 'required|integer|exists:programas,id',
            'fecha'       => 'required|date|after_or_equal:today',
            'personas'    => 'required|integer|min:1|max:50',
        ]);

        $telefono   = $this->normalizarTelefono($request->telefono);
        $programaId = (int) $request->programa_id;
        $fecha      = $request->fecha;
        $personas   = (int) $request->personas;
        $nombre     = trim($request->nombre);
        $email      = strtolower(trim($request->email));

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

        // ── 3. Crear reserva ──────────────────────────────────────────────────
        $reservaId = DB::table('reservas')->insertGetId([
            'cliente_id'        => $clienteId,
            'cantidad_personas' => $personas,
            'cantidad_masajes'  => 0,
            'fecha_visita'      => $fecha,
            'observacion'       => 'Reserva creada por bot WhatsApp',
            'id_programa'       => $programaId,
            'user_id'           => $this->getBotUserId(),
            'estado'            => 'pendiente_pago',
            'fuente'            => 'bot_whatsapp',
            'menu_recibido'     => 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        Log::info('BotReservaController: reserva creada', [
            'reserva_id'  => $reservaId,
            'cliente_id'  => $clienteId,
            'telefono'    => $telefono,
            'programa_id' => $programaId,
            'fecha'       => $fecha,
            'personas'    => $personas,
        ]);

        // ── 4. Obtener nombre del programa ────────────────────────────────────
        $programa = DB::table('programas')->where('id', $programaId)->first();
        $valorTotal = ($programa ? (int) $programa->valor_programa : 0) * $personas;
        $abono50    = (int) ceil($valorTotal / 2);

        return response()->json([
            'ok'         => true,
            'reserva_id' => $reservaId,
            'programa'   => $programa ? $programa->nombre_programa : 'Programa',
            'fecha'      => $fecha,
            'personas'   => $personas,
            'valor_total' => $valorTotal,
            'valor_total_formato' => '$' . number_format($valorTotal, 0, ',', '.'),
            'abono_50'   => $abono50,
            'abono_50_formato' => '$' . number_format($abono50, 0, ',', '.'),
            'instrucciones_pago' => [
                'transferencia' => [
                    'abono'    => '50% al reservar: ' . '$' . number_format($abono50, 0, ',', '.'),
                    'saldo'    => '50% el día de visita: ' . '$' . number_format($abono50, 0, ',', '.'),
                    'enviar_comprobante_a' => '+56974484112 o hola@botacura.cl',
                ],
                'link_pago' => [
                    'monto'    => '100% anticipado: ' . '$' . number_format($valorTotal, 0, ',', '.'),
                    'nota'     => 'Solicita el link de pago al equipo de Botacura',
                ],
            ],
            'mensaje_siguiente' => 'Para confirmar tu reserva, envía el comprobante de pago al +56974484112 o a hola@botacura.cl indicando tu nombre y fecha de visita.',
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
        $poolFlexible    = ['terraza', 'reposera'];
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
