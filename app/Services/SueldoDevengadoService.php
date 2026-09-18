<?php

namespace App\Services;

use App\HonorarioBte;
use App\Sueldo;
use App\SueldoPagado;
use App\User;
use Carbon\Carbon;

/**
 * Calcula el total de sueldos DEVENGADOS de un mes (por semana trabajada:
 * neto/BTE-neto + propinas + bono), usando exactamente la misma lógica que
 * SueldoController::index() arma para la vista /sueldos. Se extrajo acá
 * para que /utilidad (ReporteFinancieroController) use el mismo criterio
 * y no vuelvan a aparecer números distintos para "sueldos del mes" entre
 * las dos pantallas.
 *
 * Devengado = corresponde al trabajo hecho ese mes, sin importar cuándo
 * se hizo la transferencia bancaria (eso sería base caja, que es lo que
 * medía antes sueldos_pagados.fecha_pago).
 */
class SueldoDevengadoService
{
    public function totalMes($anio, $mes)
    {
        $sueldos = Sueldo::with('user')
            ->whereMonth('dia_trabajado', $mes)
            ->whereYear('dia_trabajado', $anio)
            ->orderBy('dia_trabajado')
            ->get();

        if ($sueldos->isEmpty()) {
            return 0;
        }

        $honorariosPorRut = HonorarioBte::anio($anio)
            ->whereIn('rut_emisor', User::where('boletea', true)->whereNotNull('rut')->pluck('rut'))
            ->get()
            ->groupBy('rut_emisor');

        $semanas = [];

        foreach ($sueldos as $sueldo) {
            if (! $sueldo->user) {
                continue;
            }

            $rawDate      = $sueldo->getAttributes()['dia_trabajado'];
            $fecha        = Carbon::parse($rawDate);
            $inicioSemana = $fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana    = $fecha->copy()->endOfWeek(Carbon::SUNDAY);
            $rangoKey     = $inicioSemana->format('Y-m-d') . '_' . $finSemana->format('Y-m-d');

            $roles  = $sueldo->user->list_roles();
            $esMaso = is_array($roles) ? in_array('Masoterapeuta', $roles)
                : (stripos((string) $roles, 'Masoterapeuta') !== false);

            $userId = $sueldo->user->id;

            if (! isset($semanas[$rangoKey])) {
                $semanas[$rangoKey] = [];
            }

            if (! isset($semanas[$rangoKey][$userId])) {
                $boletea = (bool) $sueldo->user->boletea;
                $bteRow  = null;

                if ($boletea && $sueldo->user->rut) {
                    $bteSemana = $honorariosPorRut->get($sueldo->user->rut, collect());
                    $bteRow    = $bteSemana->first(function ($h) use ($inicioSemana, $finSemana) {
                        return $h->fecha_emision
                            && $h->fecha_emision->between($inicioSemana->copy()->startOfDay(), $finSemana->copy()->endOfDay());
                    });
                }

                $semanas[$rangoKey][$userId] = [
                    'sueldos'   => 0,
                    'propinas'  => 0,
                    'bono'      => 0,
                    'boletea'   => $boletea,
                    'bte_bruto' => $bteRow->monto_bruto ?? 0,
                    'bte_neto'  => $bteRow ? ($bteRow->monto_pagado ?: ($bteRow->monto_bruto - $bteRow->monto_retenido)) : 0,
                ];
            }

            $semanas[$rangoKey][$userId]['sueldos']  += $esMaso ? $sueldo->total_pagar : $sueldo->valor_dia;
            $semanas[$rangoKey][$userId]['propinas'] += $esMaso ? 0 : ($sueldo->sub_sueldo - $sueldo->valor_dia);
        }

        // Bono guardado por semana (sueldos_pagados), igual que en /sueldos.
        // Puede haber más de una fila para la misma semana/usuario (p. ej.
        // bono agregado después con un segundo "Pagar seleccionados"), así
        // que se SUMAN en vez de sobrescribir.
        $pagos = SueldoPagado::all();
        foreach ($pagos as $pago) {
            $inicioSemana = Carbon::parse($pago->semana_inicio);
            $finSemana    = Carbon::parse($pago->semana_fin);
            $rangoKey     = $inicioSemana->format('Y-m-d') . '_' . $finSemana->format('Y-m-d');
            $userId       = $pago->user_id;

            if (isset($semanas[$rangoKey][$userId])) {
                $semanas[$rangoKey][$userId]['bono'] += (int) $pago->bono;
            }
        }

        $total = 0;
        foreach ($semanas as $usuariosSemana) {
            foreach ($usuariosSemana as $datos) {
                $netoBase = ($datos['boletea'] && $datos['bte_bruto'] > 0)
                    ? $datos['bte_neto']
                    : $datos['sueldos'];

                $total += $netoBase + $datos['propinas'] + $datos['bono'];
            }
        }

        return (int) $total;
    }
}
