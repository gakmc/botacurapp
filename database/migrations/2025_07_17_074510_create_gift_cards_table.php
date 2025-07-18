<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGiftCardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gift_cards', function (Blueprint $table) {
            $table->increments('id');
            $table->string('codigo')->unique();
            $table->integer('monto');
            $table->boolean('usada')->default(false);
            $table->date('fecha_uso')->nullable();
            $table->date('validez_hasta')->nullable();
            
            $table->string('de');
            $table->string('para');
            $table->string('correo')->nullable();
            $table->string('telefono')->nullable();
            $table->integer('cantidad_personas')->nullable();
            
            $table->unsignedInteger('id_programa')->nullable();
            $table->unsignedInteger('id_venta')->nullable();
            $table->unsignedInteger('generada_por')->nullable();
            $table->timestamps();

            $table->foreign('id_programa')->references('id')->on('programas')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('id_venta')->references('id')->on('ventas')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('generada_por')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gift_cards');
    }
}
