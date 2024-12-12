<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramaServicioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programa_servicio', function (Blueprint $table) {
            $table->Increments('id');

            // Llaves foráneas
            $table->unsignedInteger('id_programa');
            $table->foreign('id_programa')->references('id')->on('programas')->onUpdate('cascade')->onDelete('cascade');
            $table->unsignedInteger('id_servicio');
            $table->foreign('id_servicio')->references('id')->on('servicios')->onUpdate('cascade')->onDelete('cascade');

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
        Schema::dropIfExists('programa_servicio');
    }
}
