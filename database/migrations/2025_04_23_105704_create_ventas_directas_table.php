<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVentasDirectasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventas_directas', function (Blueprint $table) {
            $table->increments('id');
            $table->date('fecha');
            $table->boolean('tiene_propina')->default(false);
            $table->decimal('valor_propina',8,2)->nullable()->default(0);
            $table->integer('subtotal')->default(0);
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
        Schema::dropIfExists('ventas_directas');
    }
}
