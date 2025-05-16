<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoroPoroVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poro_poro_ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->date('fecha');

            $table->integer('total')->default(0);
            $table->unsignedInteger('id_tipo_transaccion');
            $table->unsignedInteger('id_user');
            $table->timestamps();

            $table->foreign('id_tipo_transaccion')->references('id')->on('tipos_transacciones')->onUpdate('cascade');
            $table->foreign('id_user')->references('id')->on('users')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poro_poro_ventas');
    }
}
