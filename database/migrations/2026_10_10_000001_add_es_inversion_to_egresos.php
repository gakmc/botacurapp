<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marca egresos que son inversion (remodelacion, activo fijo) para que el
 * informe de utilidad no los cuente como gasto de operacion del mes.
 */
class AddEsInversionToEgresos extends Migration
{
    public function up()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->boolean('es_inversion')->default(false)->after('estado');
        });
    }

    public function down()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->dropColumn('es_inversion');
        });
    }
}
