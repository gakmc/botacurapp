<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Historial del caso por cliente: derivaciones, alertas, respuestas del equipo, mensajes que el
 * cliente escribió mientras el equipo atendía, cuándo el bot retomó y reservas creadas.
 * Usa la tabla bot_eventos (la misma de Aprendizajes) y se resume en el CONTEXTO DEL CLIENTE
 * para que el bot entienda qué pasó antes, aunque el historial de mensajes ya se haya recortado.
 */
class BotCasoService
{
    const TIPOS = [
        'derivado', 'alerta_enviada', 'respuesta_equipo', 'mensaje_en_espera', 'bot_retoma',
        'hold_creado', 'reserva_creada', 'reserva_fallida', 'error_ia', 'monto_bloqueado',
    ];

    public static function registrar(string $tipo, ?string $telefono, array $detalle = [])
    {
        BotAprendizajeService::evento($tipo, $telefono ? preg_replace('/\D/', '', $telefono) : null, $detalle);
    }

    private static function variantes(string $tel): array
    {
        $tel = preg_replace('/\D/', '', $tel);
        $corto = substr($tel, -9);
        return array_values(array_unique([$tel, $corto, '56' . $corto]));
    }

    /** Id del ultimo reinicio con frase clave: lo anterior ya no cuenta como historial del caso. */
    private static function ultimoReinicio(string $tel): int
    {
        try {
            return (int) DB::table('bot_eventos')->whereIn('telefono', self::variantes($tel))
                ->where('tipo', 'conversacion_reiniciada')->max('id');
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Eventos del caso (más antiguo primero). */
    public static function eventos(string $tel, int $dias = 60, int $max = 20)
    {
        try {
            return DB::table('bot_eventos')
                ->whereIn('telefono', self::variantes($tel))
                ->whereIn('tipo', self::TIPOS)
                ->where('created_at', '>=', now()->subDays($dias))
                ->where('id', '>', self::ultimoReinicio($tel))
                ->orderBy('id', 'desc')->limit($max)->get()->reverse()->values();
        } catch (\Throwable $e) {
            Log::warning('[BotCaso] No se pudo leer el historial: ' . $e->getMessage());
            return collect();
        }
    }

    /** Texto para el prompt. Vacío si no hay nada relevante. */
    public static function resumen(string $tel): string
    {
        $out = [];
        foreach (self::eventos($tel) as $ev) {
            $d = json_decode($ev->detalle ?? '', true) ?: [];
            $cuando = Carbon::parse($ev->created_at)->timezone('America/Santiago')->locale('es')->isoFormat('ddd D MMM HH:mm');
            $txt = function ($k) use ($d) {
                return isset($d[$k]) ? '"' . mb_substr(trim(preg_replace('/\s+/', ' ', (string) $d[$k])), 0, 220) . '"' : '';
            };
            switch ($ev->tipo) {
                case 'derivado':          $l = 'Se derivó a una persona. Motivo: ' . ($d['motivo'] ?? 'sin motivo') . ($txt('ultimo_mensaje') ? '. Último mensaje del cliente: ' . $txt('ultimo_mensaje') : ''); break;
                case 'alerta_enviada':    $l = 'Se avisó al equipo (' . ($d['canal'] ?? 'alerta') . ')' . (!empty($d['motivo']) ? ': ' . $d['motivo'] : ''); break;
                case 'respuesta_equipo':  $l = 'El equipo le respondió: ' . $txt('texto'); break;
                case 'mensaje_en_espera': $l = 'El cliente escribió mientras ' . (($d['motivo'] ?? '') === 'bot_apagado' ? 'el bot estaba apagado' : 'lo atendía el equipo') . ': ' . $txt('texto'); break;
                case 'bot_retoma':        $l = 'El bot retomó la conversación' . (!empty($d['sin_respuesta_equipo']) ? ' (el equipo no alcanzó a responder)' : ''); break;
                case 'hold_creado':       $l = 'Se apartó un cupo (hold' . (!empty($d['reserva_id']) ? ' N°' . $d['reserva_id'] : '') . ') esperando transferencia'; break;
                case 'reserva_creada':    $l = 'Se creó la reserva' . (!empty($d['reserva_id']) ? ' N°' . $d['reserva_id'] : ''); break;
                case 'reserva_fallida':   $l = 'Falló un intento de reserva (' . ($d['motivo'] ?? $d['error'] ?? 'sin detalle') . ')'; break;
                case 'error_ia':          $l = 'Hubo un error técnico del bot'; break;
                case 'monto_bloqueado':   $l = 'Se bloqueó una respuesta del bot con montos no verificados'; break;
                default:                  $l = $ev->tipo;
            }
            $out[] = "- {$cuando}: {$l}.";
        }
        return implode("\n", $out);
    }

    /** ¿Hubo respuesta del equipo después de la última derivación? */
    public static function equipoRespondioTrasDerivar(string $tel): bool
    {
        $ultimoDerivado = null;
        $respondio = false;
        foreach (self::eventos($tel, 7, 40) as $ev) {
            if ($ev->tipo === 'derivado') {
                $ultimoDerivado = $ev->id;
                $respondio = false;
            } elseif ($ev->tipo === 'respuesta_equipo') {
                $respondio = true;
            }
        }
        return $ultimoDerivado === null ? true : $respondio;
    }
}
