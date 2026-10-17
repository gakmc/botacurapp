<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BotEtapa2Ubicaciones extends Migration
{
    public function up()
    {
        // Textos de las fotos de ubicaciones (se editan en la pestaña "Ubicaciones")
        $i = 0;
        foreach (\App\Services\BotUbicacionesService::tiposFoto() as $tipo => $d) {
            $i++;
            if (!DB::table('bot_secciones_texto')->where('clave', 'foto_texto_' . $tipo)->exists()) {
                DB::table('bot_secciones_texto')->insert([
                    'categoria' => 'ubicaciones', 'clave' => 'foto_texto_' . $tipo, 'etiqueta' => 'Texto de la foto: ' . $d[0],
                    'contenido' => $d[1], 'orden' => $i, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        // Guion: PASO 3C — Ubicación (después de 3B)
        if (!DB::table('bot_pasos_flujo')->where('numero_paso', '3C')->exists()) {
            $orden3B = (int) (DB::table('bot_pasos_flujo')->where('numero_paso', '3B')->value('orden') ?: 4);
            DB::table('bot_pasos_flujo')->where('orden', '>', $orden3B)->increment('orden');
            DB::table('bot_pasos_flujo')->insert([
                'numero_paso'   => '3C',
                'titulo'        => 'Ubicación',
                'instrucciones' => "Para programas Wellness (Wellness Day, Wellness Plus y sus versiones S) y programas con estación (Relax Day, Full Day y sus versiones S): apenas tengas programa, fecha con disponibilidad y cantidad de personas, usa la acción \"ubicaciones\".\n"
                    . "- Si el sistema indica que pueden elegir (2 o 4 personas en Wellness): el sistema envía una foto de la terraza y otra de la reposera. Pregunta cuál prefieren y guarda datos.ubicacion_preferida (\"terraza\" o \"reposera\").\n"
                    . "- Si no hay elección: cuéntale en una línea dónde estarán (texto_cliente) y sigue con el siguiente paso.\n"
                    . "- Si no queda ubicación disponible: usa escalar_humano.",
                'activo'        => 1,
                'bloqueado'     => 0,
                'orden'         => $orden3B + 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('bot_secciones_texto')->where('categoria', 'ubicaciones')->delete();
        $fila = DB::table('bot_pasos_flujo')->where('numero_paso', '3C')->first();
        if ($fila) {
            DB::table('bot_pasos_flujo')->where('id', $fila->id)->delete();
            DB::table('bot_pasos_flujo')->where('orden', '>', $fila->orden)->decrement('orden');
        }
    }
}
