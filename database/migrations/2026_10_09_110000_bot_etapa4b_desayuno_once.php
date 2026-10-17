<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BotEtapa4bDesayunoOnce extends Migration
{
    public function up()
    {
        DB::table('bot_secciones_texto')->where('clave', 'menu_texto')
            ->where('contenido', 'Te dejo nuestro menú 🍽️ Cuando lo revisen, me cuentan qué elige cada persona (1 entrada, 1 fondo y 1 acompañamiento).')
            ->update(['contenido' => 'Te dejo nuestro menú 🍽️ Cuando lo revisen, me cuentan qué elige cada persona: 1 entrada, 1 fondo y 1 acompañamiento. Si su programa incluye desayuno u once, indíquenme también cuál prefiere cada uno.', 'updated_at' => now()]);
    }

    public function down()
    {
    }
}
