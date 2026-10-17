<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Desayuno u once por reserva: fuente única = tabla reserva_desayuno_once
 * (la misma que usa el backoffice para los contadores del día).
 * Una fila con tipo 'desayuno' u 'once'; dos filas = las 3 comidas.
 * confirmado_at = el equipo confirmó en el recinto que el cliente pasó a esa comida.
 */
class DesayunoOnceService
{
    public static function tieneConfirmacion(int $reservaId): bool
    {
        if (!Schema::hasColumn('reserva_desayuno_once', 'confirmado_at')) {
            return false;
        }
        return DB::table('reserva_desayuno_once')->where('id_reserva', $reservaId)->whereNotNull('confirmado_at')->exists();
    }

    /** Devuelve false si no se cambió (ya confirmada o error). */
    public static function fijar(int $reservaId, array $tipos, ?int $userId = null): bool
    {
        $tipos = array_values(array_unique(array_intersect($tipos, ['desayuno', 'once'])));
        if (!$tipos) {
            return false;
        }
        if (self::tieneConfirmacion($reservaId)) {
            Log::info('[DesayunoOnce] No se cambia: ya está confirmada en el recinto', ['reserva' => $reservaId, 'tipos' => $tipos]);
            return false;
        }
        try {
            DB::transaction(function () use ($reservaId, $tipos) {
                DB::table('reserva_desayuno_once')->where('id_reserva', $reservaId)->whereIn('tipo', ['desayuno', 'once'])->delete();
                foreach ($tipos as $i => $t) {
                    try {
                        DB::table('reserva_desayuno_once')->insert(['id_reserva' => $reservaId, 'tipo' => $t, 'created_at' => now(), 'updated_at' => now()]);
                    } catch (\Throwable $e) {
                        if ($i === 0) {
                            throw $e;
                        }
                        Log::warning('[DesayunoOnce] La tabla admite una sola fila por reserva; se guardó solo ' . $tipos[0]);
                    }
                }
            });
            Log::info('[DesayunoOnce] Elección registrada', ['reserva' => $reservaId, 'tipos' => $tipos, 'user' => $userId]);
            return true;
        } catch (\Throwable $e) {
            Log::error('[DesayunoOnce] No se pudo registrar: ' . $e->getMessage(), ['reserva' => $reservaId]);
            return false;
        }
    }

    /** Marca o desmarca que el cliente pasó a la comida. Devuelve null si no existe esa elección. */
    public static function confirmar(int $reservaId, string $tipo, bool $valor, ?int $userId = null): ?array
    {
        $q = DB::table('reserva_desayuno_once')->where('id_reserva', $reservaId)->where('tipo', $tipo);
        if (!$q->exists()) {
            return null;
        }
        $ahora = now();
        $q->update([
            'confirmado_at'  => $valor ? $ahora : null,
            'confirmado_por' => $valor ? $userId : null,
            'updated_at'     => $ahora,
        ]);
        Log::info('[DesayunoOnce] ' . ($valor ? 'Confirmado' : 'Confirmación quitada'), ['reserva' => $reservaId, 'tipo' => $tipo, 'user' => $userId]);
        return ['confirmado' => $valor, 'hora' => $valor ? $ahora->format('H:i') : null, 'por' => $valor ? self::nombreUsuario($userId) : null];
    }

    public static function nombreUsuario($userId): ?string
    {
        if (!$userId) {
            return null;
        }
        try {
            return DB::table('users')->where('id', $userId)->value('name');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function textoConfirmado($fila): string
    {
        if (!$fila || empty($fila->confirmado_at)) {
            return '';
        }
        $t = 'Confirmado ' . \Carbon\Carbon::parse($fila->confirmado_at)->format('H:i');
        $n = self::nombreUsuario($fila->confirmado_por ?? null);
        return $n ? $t . ' · ' . $n : $t;
    }

    public static function incluye($reserva): bool
    {
        $p = $reserva->programa ?? null;
        return $p && $p->servicios->contains(function ($s) {
            $n = mb_strtolower($s->nombre_servicio);
            return strpos($n, 'desayuno') !== false || strpos($n, 'once') !== false;
        });
    }
}
