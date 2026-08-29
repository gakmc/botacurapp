<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAbonosExtraTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('abonos_extra', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('id_venta')->unsigned();
            $table->foreign('id_venta')->references('id')->on('ventas')->onUpdate('cascade')->onDelete('cascade');

            $table->integer('monto');
            $table->date('fecha_abono');

            $table->integer('id_tipo_transaccion')->unsigned();
            $table->foreign('id_tipo_transaccion')->references('id')->on('tipos_transacciones')->onUpdate('cascade')->onDelete('cascade');

            $table->string('folio')->nullable();

            $table->integer('user_id')->unsigned()->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');

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
        Schema::dropIfExists('abonos_extra');
    }
}
