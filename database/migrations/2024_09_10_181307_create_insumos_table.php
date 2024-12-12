<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsumosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->Increments('id');
            $table->string('nombre');
            $table->integer('valor');
            $table->integer('cantidad');
            $table->integer('stock_critico');

            // Llaves foráneas
            $table->unsignedInteger('id_unidad_medida')->nullable();
            $table->foreign('id_unidad_medida')->references('id')->on('unidades_medidas')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('id_sector')->nullable();
            $table->foreign('id_sector')->references('id')->on('sectores')
                ->onUpdate('cascade')
                ->onDelete('cascade');

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
        Schema::dropIfExists('insumos');
    }
}
