<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BotEtapa4Menu extends Migration
{
    public function up()
    {
        $i = 0;
        foreach (\App\Services\BotMenuService::ajustesDefault() as $clave => $d) {
            $i++;
            if (!DB::table('bot_secciones_texto')->where('clave', $clave)->exists()) {
                DB::table('bot_secciones_texto')->insert([
                    'categoria' => 'menu', 'clave' => $clave, 'etiqueta' => $d[0], 'contenido' => $d[1],
                    'orden' => $i, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        DB::table('bot_secciones_texto')->where('categoria', 'menu')->delete();
    }
}
