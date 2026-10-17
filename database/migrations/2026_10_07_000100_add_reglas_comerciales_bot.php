<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddReglasComercialesBot extends Migration
{
    public function up()
    {
        $def = \App\Services\BotPromptService::reglasDefault();
        $etq = \App\Services\BotPromptService::reglasEtiquetas();
        $i = 0;
        foreach ($def as $clave => $texto) {
            $i++;
            if (!DB::table('bot_secciones_texto')->where('clave', $clave)->exists()) {
                DB::table('bot_secciones_texto')->insert([
                    'categoria' => 'reglas', 'clave' => $clave, 'etiqueta' => $etq[$clave] ?? $clave,
                    'contenido' => $texto, 'orden' => $i, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('bot_secciones_texto')->where('categoria', 'reglas')->delete();
    }
}
