<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarioMovimientoDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_movimiento_detalles', function (Blueprint $table) {
            $table->Increments('id');

            $table->unsignedInteger('movimiento_id');

            $table->unsignedInteger('id_insumo');

            // Cantidad ingresada/egresada tal como el usuario la ingresó
            $table->integer('cantidad')->nullable();
            $table->unsignedInteger('id_unidad_medida')->nullable();

            // Cantidad convertida a unidad base (RECOMENDADO para no fallar con unidades)
            $table->integer('cantidad_base');

            // trazabilidad de costos
            $table->integer('costo_unitario')->nullable();
            $table->integer('costo_total')->nullable();

            $table->timestamps();

            $table->index('movimiento_id');
            $table->index('id_insumo');
            $table->index('id_unidad_medida');

            $table->foreign('movimiento_id')->references('id')->on('inventario_movimientos')->onDelete('cascade');
            $table->foreign('id_insumo')->references('id')->on('insumos')->onDelete('restrict');

            $table->foreign('id_unidad_medida')->references('id')->on('unidades_medidas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario_movimiento_detalles');
    }
}
