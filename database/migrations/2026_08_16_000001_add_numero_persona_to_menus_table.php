<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega numero_persona a la tabla menus.
 *
 * Permite distinguir el menú de cada persona dentro de una misma reserva.
 * El cliente declara su propio número (ej. "persona 1", "persona 2") y el
 * bot lo guarda tal cual. Junto con id_reserva forma una clave única, de
 * modo que si la misma persona corrige su selección, se actualiza el
 * registro en vez de duplicarse.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class AddNumeroPersonaToMenusTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('menus', 'numero_persona')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->unsignedTinyInteger('numero_persona')->nullable()
                      ->comment('Número de persona declarado por el cliente dentro de la reserva')
                      ->after('id_reserva');
            });
        }

        // Unique compuesta: una fila por (reserva, persona). Se agrega en un
        // segundo paso porque MySQL no permite índice único sobre columna
        // recién creada nullable en la misma sentencia ALTER de forma fiable
        // en todas las versiones, y así evitamos fallar si ya existe.
        $indexExists = collect(\DB::select("SHOW INDEX FROM menus WHERE Key_name = 'menus_id_reserva_numero_persona_unique'"))->isNotEmpty();
        if (!$indexExists) {
            Schema::table('menus', function (Blueprint $table) {
                $table->unique(['id_reserva', 'numero_persona'], 'menus_id_reserva_numero_persona_unique');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('menus', 'numero_persona')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->dropUnique('menus_id_reserva_numero_persona_unique');
                $table->dropColumn('numero_persona');
            });
        }
    }
}
