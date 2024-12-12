<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagoConsumosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pago_consumos', function (Blueprint $table) {
            $table->increments('id');
            $table->Integer('valor_consumo');
            $table->string('imagen_transaccion')->nullable();
            $table->unsignedInteger('id_consumo');
            $table->unsignedInteger('id_tipo_transaccion');
            $table->timestamps();
    
            // Llaves foráneas
            $table->foreign('id_consumo')->references('id')->on('consumos')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_tipo_transaccion')->references('id')->on('tipos_transacciones')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pago_consumos');
    }
}
