<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InfografiasProgramaSeeder extends Seeder
{
    /**
     * Asigna la URL pública de la infografía a cada programa.
     * Las imágenes deben estar en /public/infografias/ del servidor.
     *
     * Ejecutar con:
     *   php artisan db:seed --class=InfografiasProgramaSeeder
     */
    public function run()
    {
        $base = 'https://app.botacura.cl/infografias/';

        $asignaciones = [
            // ID  => archivo de infografía
            28 => $base . 'relax-day.jpg',     // Relax Day
            26 => $base . 'wellness-day.jpg',   // Wellness Day
            3  => $base . 'full-day.jpg',       // Full Day
            1  => $base . 'spa-day.jpg',        // Spa Day
            29 => $base . 'grupal-1.jpg',       // Grupal 1
            30 => $base . 'grupal-2.jpg',       // Grupal 2
        ];

        foreach ($asignaciones as $id => $url) {
            $filas = DB::table('programas')
                ->where('id', $id)
                ->update(['imagen_url' => $url]);

            $nombre = DB::table('programas')->where('id', $id)->value('nombre_programa');

            if ($filas > 0) {
                $this->command->info("[InfografiasProgramaSeeder] ID {$id} ({$nombre}) → {$url}");
            } else {
                $this->command->warn("[InfografiasProgramaSeeder] ID {$id} no encontrado.");
            }
        }

        $this->command->info("[InfografiasProgramaSeeder] Completado.");
    }
}
