<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservaDesayunoOnceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reserva_desayuno_once', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('id_reserva')->unsigned();
            $table->foreign('id_reserva')->references('id')->on('reservas')->onUpdate('cascade')->onDelete('cascade');

            $table->enum('tipo', ['desayuno', 'once']);

            $table->timestamps();

            $table->unique(['id_reserva', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reserva_desayuno_once');
    }
}
