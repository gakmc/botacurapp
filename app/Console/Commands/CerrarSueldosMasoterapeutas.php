<?php

namespace App\Console\Commands;

use App\Masaje;
use App\Sueldo;
use App\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CerrarSueldosMasoterapeutas extends Command
{
 protected $signature = 'cerrar:sueldos_masoterapeutas';
    protected $description = 'Cierra los sueldos de masoterapeutas según masajes realizados en la semana';

    public function handle()
    {
        Carbon::setLocale('es');

        $masoterapeutas = User::whereHas('roles', function ($q) {
            $q->where('name', 'masoterapeuta');
        })->get();

        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();
        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        // Calcular fechas exactas por día
        $fechasDiasSemana = [];
        foreach ($diasSemana as $i => $dia) {
            $fechasDiasSemana[$dia] = $inicioSemana->copy()->addDays($i)->toDateString();
        }

        $sueldosAGuardar = [];

        foreach ($masoterapeutas as $maso) {
            // Agrupar masajes por día (según fecha de visita de la reserva)
            $masajesPorDia = Masaje::where('user_id', $maso->id)
                ->whereHas('reserva', function ($q) use ($inicioSemana, $finSemana) {
                    $q->whereBetween('fecha_visita', [$inicioSemana->format('Y-m-d'), $finSemana->format('Y-m-d')]);
                })
                ->with('reserva')
                ->get()
                ->groupBy(function ($masaje) {
                    return Carbon::parse($masaje->reserva->fecha_visita)->locale('es')->isoFormat('dddd');
                });

            foreach ($diasSemana as $dia) {
                $cantidad = isset($masajesPorDia[$dia]) ? $masajesPorDia[$dia]->count() : 0;
                $monto = $cantidad * $maso->salario;

                if ($monto > 0) {
                    $sueldosAGuardar[] = [
                        'dia_trabajado' => $fechasDiasSemana[$dia],
                        'id_user' => $maso->id,
                        'valor_dia' => $maso->salario,
                        'sub_sueldo' => $monto,
                        'total_pagar' => $monto,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Guardar
        foreach ($sueldosAGuardar as $sueldo) {
            Sueldo::updateOrCreate(
                [
                    'dia_trabajado' => $sueldo['dia_trabajado'],
                    'id_user' => $sueldo['id_user'],
                ],
                [
                    'valor_dia' => $sueldo['valor_dia'],
                    'sub_sueldo' => $sueldo['sub_sueldo'],
                    'total_pagar' => $sueldo['total_pagar'],
                    'updated_at' => now(),
                ]
            );
        }

        $this->info('Sueldos de masoterapeutas cerrados correctamente.');
    }
}
