<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoroDetalleVentasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('poro_detalle_ventas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('poro_venta_id');
            $table->unsignedInteger('poro_id');
            $table->integer('cantidad');
            $table->integer('precio_unitario');
            $table->integer('subtotal');
            $table->timestamps();

            $table->unique(['poro_venta_id', 'poro_id']);
            $table->foreign('poro_venta_id')->references('id')->on('poro_poro_ventas')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('poro_id')->references('id')->on('poro_poros')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('poro_detalle_ventas');
    }
}
