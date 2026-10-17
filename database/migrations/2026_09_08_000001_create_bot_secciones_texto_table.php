<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotSeccionesTextoTable extends Migration
{
    /**
     * Contenido editable del prompt del bot: perfil de vendedor + info del recinto
     * (horarios, políticas, recomendaciones). Cada fila es un bloque de texto libre
     * identificado por una clave fija que BotPromptService interpola al armar el prompt.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bot_secciones_texto', function (Blueprint $table) {
            $table->increments('id');
            $table->string('clave')->unique();
            $table->string('categoria'); // perfil | recinto
            $table->string('etiqueta'); // nombre legible para la vista de edición
            $table->longText('contenido');
            $table->integer('orden')->default(0);
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bot_secciones_texto');
    }
}
