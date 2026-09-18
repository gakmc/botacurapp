<?php

namespace App\Console\Commands;

use App\Sueldo;
use Illuminate\Console\Command;

/**
 * Segunda parte de la corrección del 2026-08-13: sueldos:corregir-turno-13agosto
 * ya actualizó la tabla de propinas (propina_user), pero lo que se muestra en
 * las vistas de sueldo (columna "Propinas" / "Total a Pagar") sale de
 * sueldos.sub_sueldo y sueldos.total_pagar, que quedaron congelados desde el
 * cierre semanal y nunca se actualizan solos.
 *
 * Este comando suma el share de Sebastián ($2.167,50 / 3 = $722,50) al
 * sub_sueldo y total_pagar del 2026-08-13 de cada uno de los 3 que sí
 * trabajaron ese día (Juan Guzmán #31, Paula Riquelme #7, Natalia Madariaga #11).
 *
 * Uso: php artisan sueldos:sumar-propina-13agosto --dry-run
 */
class SumarPropinaSueldo13Agosto extends Command
{
    protected $signature = 'sueldos:sumar-propina-13agosto {--dry-run}';

    protected $description = 'Suma el share de propina de Sebastián al sub_sueldo/total_pagar del 13-08 de los 3 restantes';

    const FECHA = '2026-08-13';
    const INCREMENTO = 722.50; // (1300+2150+3840+1380)/4 / 3
    const USER_IDS = [31, 7, 11]; // Juan Guzmán, Paula Riquelme, Natalia Madariaga

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'MODO DRY-RUN: no se escribe nada.' : 'APLICANDO...');
        $this->line('');

        foreach (self::USER_IDS as $userId) {
            $sueldo = Sueldo::where('id_user', $userId)
                ->whereDate('dia_trabajado', self::FECHA)
                ->first();

            if (!$sueldo) {
                $this->warn("user_id={$userId}: no se encontró sueldo para " . self::FECHA);
                continue;
            }

            $nuevoSubSueldo  = $sueldo->sub_sueldo + self::INCREMENTO;
            $nuevoTotalPagar = $sueldo->total_pagar + self::INCREMENTO;

            $this->line("#{$sueldo->id} user_id={$userId} | sub_sueldo {$sueldo->sub_sueldo} -> {$nuevoSubSueldo} | total_pagar {$sueldo->total_pagar} -> {$nuevoTotalPagar}");

            if (!$dryRun) {
                $sueldo->sub_sueldo  = $nuevoSubSueldo;
                $sueldo->total_pagar = $nuevoTotalPagar;
                $sueldo->save();
            }
        }

        $this->line('');
        $this->info($dryRun ? 'Dry-run completo.' : 'Aplicado.');
    }
}
