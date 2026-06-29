<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EspacioTipoProgramaSeeder extends Seeder
{
    /**
     * Asigna el tipo de espacio físico a cada programa activo.
     *
     * Capacidades por tipo de espacio:
     *   estacion_economico  → 2 cupos  (ej: Wellness Day $45.000)
     *   estacion_intermedio → 2 cupos  (ej: Spa Day $55.000)
     *   estacion_full       → 5 cupos  (ej: Relax Day $70.000, Full Day $80.000)
     *   terraza             → 5 cupos  (ej: Grupal 1 y Grupal 2)
     *   reposera            → 4 cupos  (pares de reposeras)
     *
     * Ejecutar con:
     *   php artisan db:seed --class=EspacioTipoProgramaSeeder
     */
    public function run()
    {
        $asignaciones = [
            // ID  => espacio_tipo
            26 => 'estacion_economico',   // Wellness Day   $45.000
            1  => 'estacion_intermedio',  // Spa Day        $55.000
            28 => 'estacion_full',        // Relax Day      $70.000
            3  => 'estacion_full',        // Full Day       $80.000
            29 => 'terraza',              // Grupal 1       $40.000
            30 => 'terraza',              // Grupal 2       $45.000
        ];

        $actualizados = 0;

        foreach ($asignaciones as $id => $tipo) {
            $filas = DB::table('programas')
                ->where('id', $id)
                ->update(['espacio_tipo' => $tipo]);

            if ($filas > 0) {
                $programa = DB::table('programas')->where('id', $id)->value('nombre_programa');
                $this->command->info("[EspacioTipoProgramaSeeder] ID {$id} ({$programa}) → {$tipo}");
                $actualizados++;
            } else {
                $this->command->warn("[EspacioTipoProgramaSeeder] ID {$id} no encontrado o ya actualizado.");
            }
        }

        $this->command->info("[EspacioTipoProgramaSeeder] Completado: {$actualizados} programas actualizados.");
    }
}
