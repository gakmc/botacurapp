<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdUnidadMedidaToInsumoProductoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insumo_producto', function (Blueprint $table) {
            $table->integer('id_unidad_medida')->unsigned()->after('cantidad_insumo_usar');
            
            $table->foreign('id_unidad_medida')->references('id')->on('unidades_medidas')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insumo_producto', function (Blueprint $table) {
            $table->dropForeign(['id_unidad_medida']);
            $table->dropColumn('id_unidad_medida');
        });
    }
}
