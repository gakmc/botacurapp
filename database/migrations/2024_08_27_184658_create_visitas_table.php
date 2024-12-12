<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visitas', function (Blueprint $table) {
            $table->Increments('id');
            $table->time('horario_sauna')->nullable();
            $table->time('horario_tinaja')->nullable();
            $table->time('horario_masaje')->nullable();
            $table->string('trago_cortesia')->nullable();
            $table->string('alergias')->nullable();
            $table->string('observacion')->nullable();

            // Llaves foráneas
            $table->unsignedInteger('id_reserva')->nullable();
            $table->foreign('id_reserva')->references('id')->on('reservas')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedInteger('id_ubicacion')->nullable();
            $table->foreign('id_ubicacion')->references('id')->on('ubicaciones')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedInteger('id_lugar_masaje')->nullable();
            $table->foreign('id_lugar_masaje')->references('id')->on('lugares_masajes')
                ->onDelete('cascade')
                ->onUpdate('cascade');

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
        Schema::dropIfExists('visitas');
    }
}
