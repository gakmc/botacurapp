<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarioMovimientosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('tipo');     // ingreso, egreso, ajuste, merma, traspaso, nuevo
            $table->string('origen')->nullable();   // compra, venta, ajuste manual

            $table->nullableMorphs('referencia');
            $table->unsignedInteger('id_user')->nullable();
            $table->unsignedInteger('id_sector')->nullable();
            $table->text('observacion')->nullable();

            $table->timestamps();

            $table->index('tipo');
            $table->index('origen');
            $table->index('id_user');
            $table->index('id_sector');

            // Llaves foráneas
            $table->foreign('id_sector')->references('id')->on('sectores')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('id_user')->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inventario_movimientos');
    }
}
