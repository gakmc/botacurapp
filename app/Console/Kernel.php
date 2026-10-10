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

        // Libera cupos "apartados" (hold) por transferencia que nunca
        // recibieron el comprobante dentro del tiempo limite.
        $schedule->command('reservaholds:expirar')
                ->everyFiveMinutes();

        // -- Sincronizacion SII: lunes desde las 10:00 (antes domingos 19:45;
        // cambio pedido por Sebastian el 10/10/2026). Orden importa: compras y
        // honorarios primero, F29 al final porque depende de ambos.
        // Los comandos trabajan sobre "el mes en curso"; por eso, durante la
        // primera semana de cada mes se re-sincroniza tambien el mes anterior
        // (antes los documentos de sus ultimos dias nunca se importaban).
        $mesAnterior = now()->subMonthNoOverflow();
        $primeraSemana = function () {
            return now()->day <= 7;
        };

        // 1) Compras (RCV) -> egresos.
        $schedule->command('sii:importar-semana')
            ->mondays()->at('10:00')->timezone('America/Santiago');
        $schedule->command('sii:importar-semana --anio=' . $mesAnterior->year . ' --mes=' . $mesAnterior->month)
            ->mondays()->at('10:05')->timezone('America/Santiago')->when($primeraSemana);

        // 2) BHE. --forzar es necesario: el comando no re-consulta un periodo
        // que ya tiene registros.
        $schedule->command('honorarios:sync --mes=' . now()->month . ' --forzar')
            ->mondays()->at('10:15')->timezone('America/Santiago');
        $schedule->command('honorarios:sync ' . $mesAnterior->year . ' --mes=' . $mesAnterior->month . ' --forzar')
            ->mondays()->at('10:20')->timezone('America/Santiago')->when($primeraSemana);

        // 3) Resumen F29 (ventas SII + compras + honorarios ya sincronizados).
        $schedule->command('sii:sincronizar-f29')
            ->mondays()->at('10:30')->timezone('America/Santiago');
        $schedule->command('sii:sincronizar-f29 --anio=' . $mesAnterior->year . ' --mes=' . $mesAnterior->month)
            ->mondays()->at('10:35')->timezone('America/Santiago')->when($primeraSemana);
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
