<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Asignacion;
use App\Propina;
use App\Sueldo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CerrarSueldosSemanal extends Command
{
    protected $signature = 'cerrar:sueldos';
    protected $description = 'Cerrar automáticamente los sueldos de la semana';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Carbon::setLocale('es');

        $usuarios = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['anfitriona', 'barman', 'cocina', 'garzon', 'jefe local']);
        })->get();

        $inicioSemana = Carbon::now()->startOfWeek();
        $finSemana = Carbon::now()->endOfWeek();

        $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

        $asignaciones = Asignacion::with('users')
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->get()
            ->groupBy(function ($asignacion) {
                return ucfirst(Carbon::parse($asignacion->fecha)->locale('es')->isoFormat('dddd'));
            });

        $propinas = DB::table('propinas')
            ->selectRaw('fecha, SUM(cantidad) as total_subtotal')
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->groupBy('fecha')
            ->get();

        $propinasPorDia = [];
        foreach ($propinas as $detalle) {
            $dia = ucfirst(Carbon::parse($detalle->fecha)->locale('es')->isoFormat('dddd'));
            $diaTrabajado = Carbon::parse($detalle->fecha)->format('Y-m-d');
            $totalUsuarios = $asignaciones[$dia]->pluck('users')->flatten()->count() ?? 0;
            $propinaPorUsuario = $totalUsuarios > 0 ? ($detalle->total_subtotal / $totalUsuarios) : 0;

            $propinasPorDia[$dia] = [
                'propina' => $propinaPorUsuario,
                'dia_trabajado' => $diaTrabajado
            ];
        }

        // Fechas de cada día
        $fechasSemana = [];
        for ($i = 0; $i < 7; $i++) {
            $fechasSemana[$diasSemana[$i]] = $inicioSemana->copy()->addDays($i)->format('Y-m-d');
        }

        $sueldosAGuardar = [];

        foreach ($diasSemana as $dia) {
            if (!isset($asignaciones[$dia])) continue;

            foreach ($asignaciones[$dia] as $asignacion) {
                foreach ($asignacion->users as $user) {
                    $sueldo = $user->salario;
                    $propina = $propinasPorDia[$dia]['propina'] ?? 0;
                    $fechaTrabajo = $propinasPorDia[$dia]['dia_trabajado'] ?? $fechasSemana[$dia];

                    $sueldosAGuardar[] = [
                        'dia_trabajado' => $fechaTrabajo,
                        'id_user' => $user->id,
                        'valor_dia' => $sueldo,
                        'sub_sueldo' => $sueldo + $propina,
                        'total_pagar' => $sueldo + $propina,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        // Guardar o actualizar
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

        $this->info('Sueldos semanales cerrados correctamente.');
    }
}
