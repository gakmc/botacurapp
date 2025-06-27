<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSueldosPagadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sueldos_pagados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            
            $table->date('semana_inicio');
            $table->date('semana_fin');
            $table->date('fecha_pago');

            $table->integer('monto');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sueldos_pagados');
    }
}
