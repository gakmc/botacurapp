<?php
namespace App\Console\Commands;

use App\Masaje;
use App\Sueldo;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CerrarSueldosMasoterapeutas extends Command
{
    protected $signature   = 'cerrar:sueldos_masoterapeutas';
    protected $description = 'Cierra los sueldos de masoterapeutas según masajes realizados en la semana';

    // public function handle()
    // {
    //     Carbon::setLocale('es');

    //     $masoterapeutas = User::whereHas('roles', function ($q) {
    //         $q->where('name', 'masoterapeuta');
    //     })->get();

    //     $inicioSemana = Carbon::now()->startOfWeek();
    //     $finSemana    = Carbon::now()->endOfWeek();

    //     Log::channel('masoterapeutas')->info('CERRAR MASOTERAPEUTAS - INICIO', [
    //         'inicio'         => $inicioSemana->toDateString(),
    //         'fin'            => $finSemana->toDateString(),
    //         'masoterapeutas' => $masoterapeutas->count(),
    //     ]);

    //     $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

    //     // Calcular fechas exactas por día
    //     $fechasDiasSemana = [];
    //     foreach ($diasSemana as $i => $dia) {
    //         $fechasDiasSemana[$dia] = $inicioSemana->copy()->addDays($i)->toDateString();
    //     }

    //     $sueldosAGuardar = [];

    //     // foreach ($masoterapeutas as $maso) {
    //     //     // Agrupar masajes por día (según fecha de visita de la reserva)
    //     //     $masajesPorDia = Masaje::where('user_id', $maso->id)
    //     //         ->whereHas('reserva', function ($q) use ($inicioSemana, $finSemana) {
    //     //             $q->whereBetween('fecha_visita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')]);
    //     //         })
    //     //         ->with('reserva')
    //     //         ->get()
    //     //         ->groupBy(function ($masaje) {
    //     //             // Devuelve número de día (1-7) igual que en la vista
    //     //             return Carbon::parse($masaje->reserva->fecha_visita)->format('N');
    //     //         });

    //     //     foreach ($diasSemana as $indice => $dia) {
    //     //         $diaNumero = $indice + 1;
    //     //         $cantidad = isset($masajesPorDia[$diaNumero]) ? $masajesPorDia[$diaNumero]->count() : 0;

    //     //         // Fijar salario por defecto si es null
    //     //         $salario = $maso->salario ?? 8000;
    //     //         $monto = $cantidad * $salario;

    //     //         if ($monto > 0) {
    //     //             $sueldosAGuardar[] = [
    //     //                 'dia_trabajado' => $fechasDiasSemana[$dia],
    //     //                 'id_user' => $maso->id,
    //     //                 'valor_dia' => $salario,
    //     //                 'sub_sueldo' => $monto,
    //     //                 'total_pagar' => $monto,
    //     //                 'created_at' => now(),
    //     //                 'updated_at' => now(),
    //     //             ];
    //     //         }
    //     //     }

    //     // }

    //     foreach ($masoterapeutas as $maso) {
    //         $masajesPorDia = Masaje::where('user_id', $maso->id)
    //             ->whereHas('reserva', function ($q) use ($inicioSemana, $finSemana) {
    //                 $q->whereBetween('fecha_visita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')]);
    //             })
    //             ->with('reserva')
    //             ->get()
    //             ->groupBy(function ($masaje) {
    //                 return Carbon::parse($masaje->reserva->fecha_visita)->format('N');
    //             });

    //         foreach ($diasSemana as $indice => $dia) {
    //             $diaNumero = $indice + 1;

    //             if (isset($masajesPorDia[$diaNumero])) {
    //                 $normales = $masajesPorDia[$diaNumero]->where('tiempo_extra', false)->count();
    //                 $extras   = $masajesPorDia[$diaNumero]->where('tiempo_extra', true)->count();

    //                 $salario = $maso->salario ?? 8000;
    //                 $monto   = ($normales * $salario) + ($extras * ($salario * 2));
    //             } else {
    //                 $monto   = 0;
    //                 $salario = $maso->salario ?? 8000;
    //             }

    //             if ($monto > 0) {
    //                 $sueldosAGuardar[] = [
    //                     'dia_trabajado' => $fechasDiasSemana[$dia],
    //                     'id_user'       => $maso->id,
    //                     'valor_dia'     => $salario,
    //                     'sub_sueldo'    => $monto,
    //                     'total_pagar'   => $monto,
    //                     'created_at'    => now(),
    //                     'updated_at'    => now(),
    //                 ];
    //             }
    //         }
    //     }

    //     // Guardar
    //     foreach ($sueldosAGuardar as $sueldo) {
    //         Sueldo::updateOrCreate(
    //             [
    //                 'dia_trabajado' => $sueldo['dia_trabajado'],
    //                 'id_user'       => $sueldo['id_user'],
    //             ],
    //             [
    //                 'valor_dia'   => $sueldo['valor_dia'],
    //                 'sub_sueldo'  => $sueldo['sub_sueldo'],
    //                 'total_pagar' => $sueldo['total_pagar'],
    //                 'updated_at'  => now(),
    //             ]
    //         );
    //     }

    //     Log::channel('masoterapeutas')->info('CERRAR MASOTERAPEUTAS - FIN', [
    //         'total_registros_guardados' => count($sueldosAGuardar),
    //     ]);

    //     $this->info('Sueldos de masoterapeutas cerrados correctamente.');
    // }


    public function handle()
    {
        Carbon::setLocale('es');

        // Semana actual Lun-Dom
        $inicio = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $fin    = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $masoterapeutas = User::whereHas('roles', function ($q) {
            $q->where('name', 'masoterapeuta');
         })->get();

        
        $userIds = $masoterapeutas->pluck('id')->all();

        // 3) Base por usuario (override > rango rol(8) > 10000)
        $bases = $this->mapBaseRates($userIds);

        Log::channel('masoterapeutas')->info('CERRAR MASO - INICIO', [
            'inicio' => $inicio,
            'fin'    => $fin,
            'users'  => $userIds,
        ]);

        // 4) Subconsulta: último precio por tipo de masaje
        $ptmAny = DB::table('precios_tipos_masajes as p1')
            ->select('p1.id_tipo_masaje', 'p1.pago_masoterapeuta', 'p1.updated_at')
            ->whereRaw(
                'NOT EXISTS (
                    SELECT 1 FROM precios_tipos_masajes p2
                    WHERE p2.id_tipo_masaje = p1.id_tipo_masaje
                      AND p2.updated_at > p1.updated_at
                )'
            );

        // 5) Agregados por user + día dentro de la semana (MISMA QUERY QUE EN EL CONTROLADOR)
        $rows = DB::table('masajes as m')
            ->join('reservas as r', 'r.id', '=', 'm.id_reserva')
            ->join('tipos_masajes as tm', 'tm.nombre', '=', 'm.tipo_masaje')
            // precio según duración (30 normal, 60 tiempo extra)
            ->leftJoin('precios_tipos_masajes as ptm_des', function ($join) {
                $join->on('ptm_des.id_tipo_masaje', '=', 'tm.id')
                     ->whereRaw('ptm_des.duracion_minutos = CASE 
                                                               WHEN m.tiempo_extra = 1 THEN 60 
                                                               ELSE 30 
                                                             END');
            })
            // fallback: último registro de precio por tipo
            ->leftJoinSub($ptmAny, 'ptm_any', function ($join) {
                $join->on('ptm_any.id_tipo_masaje', '=', 'tm.id');
            })
            ->whereBetween('r.fecha_visita', [$inicio, $fin])
            ->whereNotNull('m.user_id')
            ->whereIn('m.user_id', $userIds)
            ->groupBy('m.user_id', DB::raw('DATE(r.fecha_visita)'))
            ->get([
                'm.user_id',
                DB::raw('DATE(r.fecha_visita) as dia'),

                // conteo de normales / extras (solo informativo)
                DB::raw('SUM(CASE WHEN m.tiempo_extra = 1 THEN 1 ELSE 0 END) as extras'),
                DB::raw('SUM(CASE WHEN m.tiempo_extra = 1 THEN 0 ELSE 1 END) as normales'),

                // suma de pagos definidos (>0) desde precios_tipos_masajes
                DB::raw('
                    SUM(
                        CASE 
                            WHEN COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta) IS NULL
                                 OR COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta) = 0
                            THEN 0
                            ELSE COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta)
                        END
                    ) as suma_ptm
                '),

                // cuántos masajes NO tienen pago definido (NULL o 0) → usar salario base del user
                DB::raw('
                    SUM(
                        CASE 
                            WHEN COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta) IS NULL
                                 OR COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta) = 0
                            THEN 1
                            ELSE 0
                        END
                    ) as faltantes
                '),
            ]);

        // 6) Preparar filas para persistir en sueldos
        $sueldosAGuardar = [];

        foreach ($rows as $row) {
            $uid   = (int) $row->user_id;
            $base  = $bases[$uid] ?? 10000; // override user > rango rol > 10000
            $total = (int) $row->suma_ptm + ((int) $row->faltantes * $base);

            // Si no hay nada que pagar, no generamos sueldo
            if ($total <= 0) {
                continue;
            }

            $sueldosAGuardar[] = [
                'dia_trabajado' => $row->dia,
                'id_user'       => $uid,
                'valor_dia'     => $base,
                'sub_sueldo'    => $total,
                'total_pagar'   => $total,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        // 7) Guardar de forma idempotente (igual que tu store_maso)
        DB::transaction(function () use ($sueldosAGuardar) {
            foreach ($sueldosAGuardar as $s) {
                Sueldo::updateOrCreate(
                    [
                        'dia_trabajado' => $s['dia_trabajado'],
                        'id_user'       => $s['id_user'],
                    ],
                    [
                        'valor_dia'   => $s['valor_dia'],
                        'sub_sueldo'  => $s['sub_sueldo'],
                        'total_pagar' => $s['total_pagar'],
                        'updated_at'  => now(),
                    ]
                );
            }
        });

        Log::channel('masoterapeutas')->info('CERRAR MASO - FIN', [
            'registros' => count($sueldosAGuardar),
            'detalle'   => $sueldosAGuardar,
        ]);

        $this->info('Sueldos de masoterapeutas cerrados correctamente.');
    }

    /**
     * Calcula salario base por usuario:
     *  - Primero override en anular_sueldo_usuarios
     *  - Luego rango_sueldo_roles para role_id = 8
     *  - Si nada, usa 10000
     */
    private function mapBaseRates(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $hoy = Carbon::now()->toDateString();

        // Overrides individuales (anular_sueldo_usuarios)
        $overrides = DB::table('anular_sueldo_usuarios')
            ->whereIn('user_id', $userIds)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id')
            ->map(function ($group) {
                return (int) $group->first()->salario;
            })
            ->toArray(); // clave: user_id

        // Rango base para el rol masoterapeuta (role_id = 8)
        $rango = (int) DB::table('rango_sueldo_roles')
            ->where('role_id', 8)
            ->whereDate('vigente_desde', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('vigente_hasta')
                  ->orWhereDate('vigente_hasta', '>=', $hoy);
            })
            ->orderByDesc('vigente_desde')
            ->value('sueldo_base');

        $default = $rango > 0 ? $rango : 10000;

        $bases = [];
        foreach ($userIds as $id) {
            $bases[$id] = $overrides[$id] ?? $default;
        }

        return $bases;
    }
}
