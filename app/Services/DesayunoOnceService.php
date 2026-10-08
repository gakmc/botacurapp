<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Desayuno u once por reserva: fuente única = tabla reserva_desayuno_once
 * (la misma que usa el backoffice para los contadores del día).
 * Una fila con tipo 'desayuno' u 'once'; dos filas = las 3 comidas.
 */
class DesayunoOnceService
{
    public static function fijar(int $reservaId, array $tipos, ?int $userId = null)
    {
        $tipos = array_values(array_unique(array_intersect($tipos, ['desayuno', 'once'])));
        if (!$tipos) {
            return;
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
        } catch (\Throwable $e) {
            Log::error('[DesayunoOnce] No se pudo registrar: ' . $e->getMessage(), ['reserva' => $reservaId]);
        }
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
