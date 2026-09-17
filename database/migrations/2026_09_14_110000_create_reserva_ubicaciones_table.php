<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservaUbicacionesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reserva_ubicaciones')) {
            return;
        }

        Schema::create('reserva_ubicaciones', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('id_reserva')->unsigned();
            $table->foreign('id_reserva')->references('id')->on('reservas')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('id_ubicacion')->unsigned();
            $table->foreign('id_ubicacion')->references('id')->on('ubicaciones')->onUpdate('cascade')->onDelete('cascade');

            $table->unsignedTinyInteger('cantidad_personas')->nullable()->comment('Personas de la reserva asignadas a esta ubicación específica');
            $table->string('rol', 50)->nullable()->comment('Motivo/rol de la asignación, ej: grupal_aceptada, fallback_normal');

            $table->timestamps();

            $table->unique(['id_reserva', 'id_ubicacion']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserva_ubicaciones');
    }
}
