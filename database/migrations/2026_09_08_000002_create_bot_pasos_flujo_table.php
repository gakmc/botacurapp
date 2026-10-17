<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotPasosFlujoTable extends Migration
{
    /**
     * Guion / flujo paso a paso que sigue el bot para recopilar datos de la reserva
     * (PASO 1 - PASO 11 del prompt actual). Editable desde el backoffice; el campo
     * "bloqueado" marca pasos cuya lógica depende de datos calculados en código
     * (ej. PASO 8, que depende de si el programa incluye masajes) para que la vista
     * avise que solo el texto es editable, no la condición.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_pasos_flujo', function (Blueprint $table) {
            $table->increments('id');
            $table->string('numero_paso'); // "1", "3B", "8"...
            $table->string('titulo');
            $table->longText('instrucciones');
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->boolean('bloqueado')->default(false);
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bot_pasos_flujo');
    }
}
