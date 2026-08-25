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
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('cerrar:sueldos')
                ->sundays()
                ->at('21:00')
                ->timezone('America/Santiago');

        $schedule->command('cerrar:sueldos_masoterapeutas')
                ->sundays()
                ->at('21:05')
                ->timezone('America/Santiago');

        $schedule->command('reservas:enviar-recordatorios')
                ->dailyAt('10:00')
                ->timezone('America/Santiago');

        // ── Sincronización SII, domingos, terminando antes del cierre de
        //    sueldos de las 21:00. Orden importa: compras y honorarios
        //    primero, F29 al final porque depende de ambos.

        // 1) Compras (RCV) → egresos. Tenía docblock diciendo "domingos 21:10"
        //    pero nunca había quedado agendado de verdad.
        $schedule->command('sii:importar-semana')
                ->sundays()
                ->at('19:45')
                ->timezone('America/Santiago');

        // 2) BHE del mes en curso. --forzar es necesario: el comando no
        //    re-consulta un período que ya tiene registros, así que sin
        //    --forzar nunca vería boletas nuevas emitidas después de la
        //    primera sincronización del mes.
        $schedule->command('honorarios:sync --mes=' . now()->month . ' --forzar')
                ->sundays()
                ->at('20:00')
                ->timezone('America/Santiago');

        // 3) Resumen F29 (ventas SII + compras + honorarios ya sincronizados).
        $schedule->command('sii:sincronizar-f29')
                ->sundays()
                ->at('20:15')
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
