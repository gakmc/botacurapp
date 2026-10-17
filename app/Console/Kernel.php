<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\CerrarSueldosSemanal::class,
        \App\Console\Commands\CerrarSueldosMasoterapeutas::class,
        \App\Console\Commands\ExpirarReservaHolds::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('bot:aprender')->dailyAt('23:40');
        // Inventario: corrige descuentos de ventas que no dispararon observer
        $schedule->command('inventario:conciliar')->hourly()->withoutOverlapping(30);
        // Detalle (productos) de las facturas recibidas, una hora después de la sync SII
        $schedule->call(function () {
            \Illuminate\Support\Facades\Artisan::call('sii:importar-detalle-compras', [
                'desde' => now()->subDays(45)->toDateString(), 'hasta' => now()->toDateString(),
            ]);
        })->name('sii-detalle-compras')->mondays()->at('11:00')->timezone('America/Santiago')->withoutOverlapping(120);

        $schedule->command('cerrar:sueldos --programado')
                ->sundays()
                ->at('21:00')
                ->timezone('America/Santiago');

        $schedule->command('cerrar:sueldos_masoterapeutas --programado')
                ->sundays()
                ->at('21:05')
                ->timezone('America/Santiago');

        $schedule->command('reservas:enviar-recordatorios --programado')
                ->dailyAt('10:00')
                ->timezone('America/Santiago');

        // Libera cupos "apartados" (hold) por transferencia que nunca
        // recibieron el comprobante dentro del tiempo limite.
        $schedule->command('reservaholds:expirar')
                ->everyFiveMinutes();

        // -- Sincronizacion SII: UNA sola ejecucion, lunes 10:00 (hora de Chile).
        // Corre en orden compras -> honorarios -> F29 (el F29 depende de los
        // otros dos). Los comandos trabajan sobre un mes; el primer lunes de
        // cada mes (dia <= 7) se procesa tambien el mes anterior, para no perder
        // los documentos de sus ultimos dias. Cambio pedido por Sebastian el
        // 10/10/2026 (antes: domingos 19:45/20:00/20:15).
        $schedule->call(function () {
            $hoy = now();
            $periodos = [];
            if ($hoy->day <= 7) {
                $ant = $hoy->copy()->subMonthNoOverflow();
                $periodos[] = [$ant->year, $ant->month];
            }
            $periodos[] = [$hoy->year, $hoy->month];

            foreach ($periodos as list($anio, $mes)) {
                $pasos = [
                    ['sii:importar-semana', ['--anio' => $anio, '--mes' => $mes, '--programado' => true]],
                    ['honorarios:sync', ['anio' => $anio, '--mes' => $mes, '--forzar' => true, '--programado' => true]],
                    ['sii:sincronizar-f29', ['--anio' => $anio, '--mes' => $mes, '--programado' => true]],
                ];
                foreach ($pasos as list($comando, $args)) {
                    try {
                        \Illuminate\Support\Facades\Artisan::call($comando, $args);
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error("Sync SII semanal: {$comando} {$anio}-{$mes} fallo: " . $e->getMessage());
                    }
                }
            }
        })->name('sii-sync-semanal')
          ->mondays()->at('10:00')->timezone('America/Santiago')
          ->withoutOverlapping(120);

        // ── Auditoría: retención según config/auditoria.php. Solo opera sobre las
        //    tablas de auditoría (nunca datos del negocio).
        $schedule->command('auditoria:archivar --programado')
                ->dailyAt('03:00')
                ->timezone('America/Santiago');

        $schedule->command('auditoria:purgar --programado')
                ->monthlyOn(1, '03:30')
                ->timezone('America/Santiago');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
