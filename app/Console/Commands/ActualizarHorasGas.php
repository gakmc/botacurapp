<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Actualiza las horas de uso en un registro de gas_instalaciones.
 * Útil para completar un registro creado sin el dato de horas.
 *
 * Uso:
 *   php artisan gas:actualizar-horas --id=1 --horas=235.4
 */
class ActualizarHorasGas extends Command
{
    protected $signature = 'gas:actualizar-horas
                            {--id=  : ID del registro en gas_instalaciones}
                            {--horas= : Horas de uso a registrar}';

    protected $description = 'Actualiza contador_anterior_valor (horas) en un registro de gas_instalaciones';

    public function handle()
    {
        $id    = $this->option('id');
        $horas = $this->option('horas');

        if (!$id || $horas === null) {
            $this->error('Uso: php artisan gas:actualizar-horas --id=X --horas=YYY');
            return 1;
        }

        $registro = DB::connection('mysql_iot')
            ->table('gas_instalaciones')
            ->find((int) $id);

        if (!$registro) {
            $this->error("No existe registro con ID #{$id} en gas_instalaciones.");
            return 1;
        }

        $this->table(
            ['Campo', 'Valor actual', 'Valor nuevo'],
            [
                ['lugar',                    $registro->lugar,                    '—'],
                ['fecha_instalacion',         $registro->fecha_instalacion,        '—'],
                ['contador_anterior_valor',   $registro->contador_anterior_valor ?? 'NULL', $horas . ' h'],
                ['contador_anterior_unidad',  $registro->contador_anterior_unidad ?? 'NULL', 'horas'],
            ]
        );

        if (!$this->confirm("¿Actualizar registro #{$id}?")) {
            $this->warn('Cancelado.');
            return 0;
        }

        DB::connection('mysql_iot')->table('gas_instalaciones')
            ->where('id', (int) $id)
            ->update([
                'contador_anterior_valor'  => (float) $horas,
                'contador_anterior_unidad' => 'horas',
                'updated_at'               => now(),
            ]);

        $h = (float) $horas;
        $this->info("✅ Registro #{$id} actualizado: {$h} horas (" . round($h / 24, 1) . " días aprox.)");

        return 0;
    }
}
