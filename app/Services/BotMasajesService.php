<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * BotMasajesService — cupo diario de masajes.
 *
 * Espacios (definidos por Sebastián, 09-10-2026):
 *  - Container: 2 personas a la vez, solo de la misma reserva y con el mismo tiempo.
 *  - Enfermería: 1 persona.
 * Turnos de 30 minutos (un masaje de 60 minutos usa 2 turnos). Turnos por día y por
 * espacio: parámetro param_masaje_slots_dia (10:20 a 19:00 = 17 turnos de 30 min).
 *
 * Cada reserva se reparte en parejas (container) y una persona suelta (enfermería);
 * si la enfermería se llena, las personas sueltas usan el container solas.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class BotMasajesService
{
    public static function slotsDia(): int
    {
        $v = 0;
        try {
            $v = (int) preg_replace('/\D/', '', (string) DB::table('bot_secciones_texto')->where('clave', 'param_masaje_slots_dia')->value('contenido'));
        } catch (\Throwable $e) {
        }
        return $v > 0 ? $v : 17;
    }

    /**
     * Función pura: ¿caben estas reservas? $turnosPorReserva = [turnos-persona de cada reserva].
     * Devuelve [ok, container_usados, enfermeria_usados].
     */
    public static function caben(array $turnosPorReserva, int $slots): array
    {
        $pares = 0;
        $sueltos = 0;
        foreach ($turnosPorReserva as $t) {
            $t = max(0, (int) $t);
            $pares   += intdiv($t, 2);
            $sueltos += $t % 2;
        }
        $enf  = min($sueltos, $slots);
        $cont = $pares + ($sueltos - $enf);
        return [$cont <= $slots, $cont, $enf];
    }

    private static function esSi($v): bool
    {
        $v = mb_strtolower(trim((string) $v));
        return $v !== '' && !in_array($v, ['0', 'no', 'false', 'null'], true);
    }

    /** Turnos-persona de masaje ya comprometidos en la fecha (reservas + cupos apartados). */
    public static function ocupados(string $fecha, ?int $excluirReserva = null): array
    {
        $out = [];
        $reservas = DB::table('reservas')
            ->whereRaw('DATE(fecha_visita) = ?', [$fecha])
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'cancelada');
            })
            ->when($excluirReserva, function ($q) use ($excluirReserva) {
                $q->where('id', '<>', $excluirReserva);
            })
            ->get(['id', 'id_programa', 'cantidad_personas', 'cantidad_masajes', 'cantidad_masajes_extra']);
        foreach ($reservas as $r) {
            $filas = DB::table('masajes')->where('id_reserva', $r->id)->get();
            if ($filas->count()) {
                $t = 0;
                foreach ($filas as $m) {
                    $t += 1 + (isset($m->tiempo_extra) && self::esSi($m->tiempo_extra) ? 1 : 0);
                }
            } else {
                $t = (BotTarifasService::incluyeMasaje((int) $r->id_programa) ? (int) $r->cantidad_personas : (int) $r->cantidad_masajes)
                    + (int) $r->cantidad_masajes_extra;
            }
            if ($t > 0) {
                $out[] = $t;
            }
        }
        try {
            $holds = DB::table('reserva_holds')->where('fecha', $fecha)->whereIn('estado', ['activo', 'pendiente'])
                ->where('expira_en', '>', now())->get(['programa_id', 'personas', 'masajes_extra']);
            foreach ($holds as $h) {
                $t = (BotTarifasService::incluyeMasaje((int) $h->programa_id) ? (int) $h->personas : 0) + (int) $h->masajes_extra;
                if ($t > 0) {
                    $out[] = $t;
                }
            }
        } catch (\Throwable $e) {
        }
        return $out;
    }

    /** ¿Cabe una reserva nueva con $turnosNuevos turnos-persona de masaje? */
    public static function disponible(string $fecha, int $turnosNuevos): array
    {
        $slots = self::slotsDia();
        $ocup  = self::ocupados($fecha);
        list($okAntes) = self::caben($ocup, $slots);
        $ocup[] = $turnosNuevos;
        list($ok, $cont, $enf) = self::caben($ocup, $slots);
        return [
            'ok'                 => $turnosNuevos <= 0 || $ok,
            'container_usados'   => $cont,
            'enfermeria_usados'  => $enf,
            'turnos_por_espacio' => $slots,
            'ya_lleno'           => !$okAntes,
        ];
    }
}
