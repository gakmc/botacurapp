<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetallesConsumosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalles_consumos', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('cantidad_producto');
            $table->integer('subtotal');
            $table->boolean('genera_propina')->default(false);

            // Llaves foráneas
            $table->unsignedInteger('id_consumo');
            $table->foreign('id_consumo')->references('id')->on('consumos')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('id_producto');
            $table->foreign('id_producto')->references('id')->on('productos')
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
        Schema::dropIfExists('detalles_consumos');
    }
}
