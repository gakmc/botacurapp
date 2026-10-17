<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BotEtapa6Masajes extends Migration
{
    public function up()
    {
        if (!DB::table('bot_secciones_texto')->where('clave', 'param_masaje_slots_dia')->exists()) {
            $orden = (int) DB::table('bot_secciones_texto')->where('categoria', 'parametros')->max('orden');
            DB::table('bot_secciones_texto')->insert([
                'categoria' => 'parametros', 'clave' => 'param_masaje_slots_dia',
                'etiqueta'  => \App\Services\BotTarifasService::parametrosDefault()['param_masaje_slots_dia'][0],
                'contenido' => '17', 'orden' => $orden + 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('bot_secciones_texto')->where('clave', 'param_masaje_slots_dia')->delete();
    }
}
