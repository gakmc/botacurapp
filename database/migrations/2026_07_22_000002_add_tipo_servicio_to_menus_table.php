<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega tipo_servicio a la tabla menus.
 *
 * Permite distinguir si el menú corresponde a un desayuno o una once,
 * según lo recopilado por el bot de WhatsApp durante la reserva.
 *
 * Valores: 'desayuno' | 'once' | null (sin especificar)
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class AddTipoServicioToMenusTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('menus', 'tipo_servicio')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->string('tipo_servicio', 20)->nullable()
                      ->comment('desayuno | once | null')
                      ->after('alergias');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('menus', 'tipo_servicio')) {
            Schema::table('menus', function (Blueprint $table) {
                $table->dropColumn('tipo_servicio');
            });
        }
    }
}
