<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservaWellnessTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reserva_wellness')) {
            return;
        }

        Schema::create('reserva_wellness', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('id_reserva')->unsigned();
            $table->foreign('id_reserva')->references('id')->on('reservas')->onUpdate('cascade')->onDelete('cascade');

            $table->enum('tipo', ['terraza', 'reposera']);

            $table->timestamps();

            $table->unique('id_reserva');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserva_wellness');
    }
}
