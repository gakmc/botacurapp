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

        $masoterapeutas = User::whereHas('roles', function($q){return $q->where('name','masoterapeuta');})
            ->get(['id','name']);

        // Base por usuario (override > rango rol(8) vigente > 8000)
        $bases = $this->mapBaseRates($masoterapeutas->pluck('id')->all());

        Log::channel('masoterapeutas')->info('CERRAR MASO - INICIO', [
            'inicio' => $inicio, 'fin' => $fin, 'count' => $masoterapeutas->count()
        ]);

        // Fallback: último precio por tipo (por si el tipo no tiene 30/60 o no tiene pago)
        $ptmAny = DB::table('precios_tipos_masajes as p1')
            ->select('p1.id_tipo_masaje','p1.pago_masoterapeuta','p1.updated_at')
            ->whereRaw('NOT EXISTS (
                SELECT 1 FROM precios_tipos_masajes p2
                WHERE p2.id_tipo_masaje=p1.id_tipo_masaje AND p2.updated_at>p1.updated_at
            )');

        // Traer agregado por usuario y día
        $rows = DB::table('masajes as m')
            ->join('reservas as r','r.id','=','m.id_reserva')
            ->join('tipos_masajes as tm','tm.nombre','=','m.tipo_masaje')
            // precio según duración deseada: 30 (tiempo_extra=0) / 60 (tiempo_extra=1)
            ->leftJoin('precios_tipos_masajes as ptm_des', function($j){
                $j->on('ptm_des.id_tipo_masaje','=','tm.id')
                ->whereRaw('ptm_des.duracion_minutos = CASE WHEN m.tiempo_extra=1 THEN 60 ELSE 30 END');
            })
            // fallback: último precio del tipo
            ->leftJoinSub($ptmAny,'ptm_any', function($j){
                $j->on('ptm_any.id_tipo_masaje','=','tm.id');
            })
            ->whereBetween('r.fecha_visita', [$inicio, $fin])
            ->whereIn('m.user_id', $masoterapeutas->pluck('id'))
            ->whereNotNull('m.user_id')
            ->groupBy('m.user_id', DB::raw('DATE(r.fecha_visita)'))
            ->get([
                'm.user_id',
                DB::raw('DATE(r.fecha_visita) as dia'),
                DB::raw('SUM(CASE WHEN m.tiempo_extra=1 THEN 1 ELSE 0 END) as extras'),
                DB::raw('SUM(CASE WHEN m.tiempo_extra=1 THEN 0 ELSE 1 END) as normales'),
                DB::raw('SUM(CASE WHEN COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta) > 0
                            THEN COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta)
                            ELSE 0 END) as suma_ptm'),
                DB::raw('SUM(CASE WHEN COALESCE(ptm_des.pago_masoterapeuta, ptm_any.pago_masoterapeuta) > 0
                            THEN 0 ELSE 1 END) as faltantes')
            ]);

        // Preparar filas para persistir
        $sueldosAGuardar = [];
        foreach ($rows as $r) {
            $uid   = (int)$r->user_id;
            $base  = $bases[$uid] ?? 8000;
            $total = (int)$r->suma_ptm + ((int)$r->faltantes * $base);

            if ($total > 0) {
                $sueldosAGuardar[] = [
                    'dia_trabajado' => $r->dia,
                    'id_user'       => $uid,
                    'valor_dia'     => $base,
                    'sub_sueldo'    => $total,
                    'total_pagar'   => $total,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
        }

        // Guardar de forma idempotente
        DB::transaction(function () use ($sueldosAGuardar) {
            foreach ($sueldosAGuardar as $s) {
                Sueldo::updateOrCreate(
                    ['dia_trabajado' => $s['dia_trabajado'], 'id_user' => $s['id_user']],
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
        ]);

        $this->info('Sueldos de masoterapeutas cerrados correctamente.');
    }

    /** ==== Helpers dentro del Command ==== */
    private function mapBaseRates(array $userIds): array
    {
        $hoy = now()->toDateString();

        $overrides = DB::table('anular_sueldo_usuarios')
            ->whereIn('user_id', $userIds)
            ->select('user_id','salario','created_at')
            ->orderByDesc('created_at')->get()
            ->groupBy('user_id')->map(function($g){return (int)$g->first()->salario;});

        $rango = (int) DB::table('rango_sueldo_roles')
            ->where('role_id',8)
            ->whereDate('vigente_desde','<=',$hoy)
            ->where(function($q) use ($hoy){
                $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta','>=',$hoy);
            })
            ->orderByDesc('vigente_desde')->value('sueldo_base');

        $bases=[];
        foreach ($userIds as $id) $bases[$id] = $overrides[$id] ?? ($rango>0?$rango:8000);
        return $bases;
    }
}
