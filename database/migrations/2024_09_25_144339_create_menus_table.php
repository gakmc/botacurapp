<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMenusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('id_visita');
            $table->unsignedInteger('id_producto_entrada')->nullable();
            $table->unsignedInteger('id_producto_fondo')->nullable();
            $table->unsignedInteger('id_producto_acompanamiento')->nullable();
            $table->string('observacion')->nullable();
            $table->timestamps();
        
            $table->foreign('id_visita')->references('id')->on('visitas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('id_producto_entrada')->references('id')->on('productos')->onUpdate('cascade');
            $table->foreign('id_producto_fondo')->references('id')->on('productos')->onUpdate('cascade');
            $table->foreign('id_producto_acompanamiento')->references('id')->on('productos')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
}
