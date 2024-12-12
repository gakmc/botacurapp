<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('abono_programa')->nullable();
            $table->string('imagen_abono')->nullable();
            $table->integer('diferencia_programa')->nullable();
            $table->string('imagen_diferencia')->nullable();
            $table->integer('descuento')->nullable();
            $table->integer('total_pagar')->nullable();

            // Llaves foráneas
            $table->unsignedInteger('id_reserva')->nullable();
            $table->foreign('id_reserva')->references('id')->on('reservas')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedInteger('id_tipo_transaccion_abono')->nullable();
            $table->foreign('id_tipo_transaccion_abono')->references('id')->on('tipos_transacciones')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->unsignedInteger('id_tipo_transaccion_diferencia')->nullable();
            $table->foreign('id_tipo_transaccion_diferencia')->references('id')->on('tipos_transacciones')
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
        Schema::dropIfExists('ventas');
    }
}
