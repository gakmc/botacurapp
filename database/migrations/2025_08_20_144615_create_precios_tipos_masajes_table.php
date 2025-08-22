<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreciosTiposMasajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('precios_tipos_masajes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_tipo_masaje');
            $table->unsignedInteger('duracion_minutos');
            $table->unsignedInteger('precio_unitario');
            $table->unsignedInteger('precio_pareja')->nullable();
            $table->timestamps();

            
            $table->foreign('id_tipo_masaje')->references('id')->on('tipos_masajes')->onUpdate('cascade')->onDelete('cascade');

            $table->unique(['id_tipo_masaje','duracion_minutos'], 'unique_tipo_duracion');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('precios_tipos_masajes');
    }
}
