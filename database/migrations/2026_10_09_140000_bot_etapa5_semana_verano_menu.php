<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class BotEtapa5SemanaVeranoMenu extends Migration
{
    public function up()
    {
        // Descuento de programas S (se aplica también sobre el recargo de verano)
        if (!DB::table('bot_secciones_texto')->where('clave', 'param_descuento_semana_pct')->exists()) {
            $orden = (int) DB::table('bot_secciones_texto')->where('categoria', 'parametros')->max('orden');
            DB::table('bot_secciones_texto')->insert([
                'categoria' => 'parametros', 'clave' => 'param_descuento_semana_pct',
                'etiqueta'  => \App\Services\BotTarifasService::parametrosDefault()['param_descuento_semana_pct'][0],
                'contenido' => '10', 'orden' => $orden + 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // Programas S todo el año (antes solo marzo a octubre), si nadie lo cambió
        DB::table('bot_secciones_texto')->where('clave', 'param_meses_semana')->where('contenido', '3,4,5,6,7,8,9,10')
            ->update(['contenido' => '1,2,3,4,5,6,7,8,9,10,11,12', 'updated_at' => now()]);
        // Texto de la regla de programas S, si nadie lo editó
        $fila = DB::table('bot_secciones_texto')->where('clave', 'regla_programas_semana')->first();
        if ($fila && preg_replace('/\s+/u', ' ', trim($fila->contenido)) === preg_replace('/\s+/u', ' ', trim('PROGRAMAS DE SEMANA (los que terminan en "S"): son el mismo programa con 10% de descuento, y su precio en la lista YA incluye ese descuento (nunca lo descuentes de nuevo). Solo para fechas de lunes a viernes habilitadas que no sean feriado, de marzo a octubre, y solo con pago por transferencia. No son reagendables. Si la fecha es un fin de semana, feriado o de noviembre a febrero, ofrece el programa regular equivalente. La acción "cotizar" valida estas condiciones.'))) {
            DB::table('bot_secciones_texto')->where('id', $fila->id)->update([
                'contenido' => \App\Services\BotPromptService::reglasDefault()['regla_programas_semana'], 'updated_at' => now(),
            ]);
        }
        // Fondos plato único (Pastel de Papas y vegetarianos), detectados por nombre; se ajustan en la pestaña Menú
        if (!DB::table('bot_secciones_texto')->where('clave', 'menu_fondos_unicos')->exists()) {
            $ids = \App\Services\BotMenuService::fondosUnicos();
            DB::table('bot_secciones_texto')->insert([
                'categoria' => 'menu', 'clave' => 'menu_fondos_unicos',
                'etiqueta'  => 'Fondos que son plato único (no llevan acompañamiento), IDs',
                'contenido' => implode(',', $ids), 'orden' => 5, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('bot_secciones_texto')->whereIn('clave', ['param_descuento_semana_pct', 'menu_fondos_unicos'])->delete();
    }
}
