<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Desayuno u once es UNA elección por reserva y se pide después del pago, junto con el menú.
 * Quita el paso 8B (si se instaló la versión anterior) y actualiza el texto del menú si nadie lo editó.
 */
class BotDesayunoOncePorReserva extends Migration
{
    public function up()
    {
        $fila = DB::table('bot_pasos_flujo')->where('numero_paso', '8B')->first();
        if ($fila) {
            DB::table('bot_pasos_flujo')->where('id', $fila->id)->delete();
            DB::table('bot_pasos_flujo')->where('orden', '>', $fila->orden)->decrement('orden');
        }
        DB::table('bot_secciones_texto')->where('clave', 'menu_texto')
            ->whereIn('contenido', ['Te dejo nuestro menú 🍽️ Cuando lo revisen, me cuentan qué elige cada persona (1 entrada, 1 fondo y 1 acompañamiento).', 'Te dejo nuestro menú 🍽️ Cuando lo revisen, me cuentan qué elige cada persona: 1 entrada, 1 fondo y 1 acompañamiento. Si su programa incluye desayuno u once, indíquenme también cuál prefiere cada uno.'])
            ->update(['contenido' => 'Te dejo nuestro menú 🍽️ Para cada persona necesitamos 1 entrada y 1 fondo (proteína + acompañamiento); el postre es del día. Si su programa incluye desayuno u once, cuéntenme cuál prefiere el grupo (es buffet).', 'updated_at' => now()]);
    }

    public function down()
    {
    }
}
