<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsumoProductoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insumo_producto', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('cantidad_insumo_usar');
            $table->integer('total_costo_producto');
            $table->integer('utilidad_producto');

            // Llaves foráneas
            $table->unsignedInteger('id_producto');
            $table->foreign('id_producto')->references('id')->on('productos')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('id_insumo');
            $table->foreign('id_insumo')->references('id')->on('insumos')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('insumo_producto');
    }
}
