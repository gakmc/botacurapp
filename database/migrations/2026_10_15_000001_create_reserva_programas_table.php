<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Varios programas en una misma reserva (15/10/2026). */
class CreateReservaProgramasTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reserva_programas')) {
            return;
        }
        Schema::create('reserva_programas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_reserva');
            $table->unsignedInteger('id_programa');
            $table->unsignedSmallInteger('cantidad_personas');
            $table->unsignedInteger('valor_unitario')->nullable();
            $table->boolean('principal')->default(false);
            $table->timestamps();

            $table->index('id_reserva');
            $table->foreign('id_reserva')->references('id')->on('reservas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('id_programa')->references('id')->on('programas')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserva_programas');
    }
}
