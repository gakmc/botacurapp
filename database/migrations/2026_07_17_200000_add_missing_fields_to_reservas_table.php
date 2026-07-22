<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columnas que existen en producción pero faltaban en las migraciones locales.
 */
class AddMissingFieldsToReservasTable extends Migration
{
    public function up()
    {
        Schema::table('reservas', function (Blueprint $table) {
            if (!Schema::hasColumn('reservas', 'estado')) {
                $table->string('estado')->nullable()->after('observacion');
            }
            if (!Schema::hasColumn('reservas', 'menu_recibido')) {
                $table->tinyInteger('menu_recibido')->default(0)->after('estado');
            }
        });
    }

    public function down()
    {
        Schema::table('reservas', function (Blueprint $table) {
            $table->dropColumn(['estado', 'menu_recibido']);
        });
    }
}
