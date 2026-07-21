<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstadoToDetallesVentasDirectasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detalles_ventas_directas', function (Blueprint $table) {
            $table->string('estado')->default('por-procesar')->after('producto_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detalles_ventas_directas', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
}
