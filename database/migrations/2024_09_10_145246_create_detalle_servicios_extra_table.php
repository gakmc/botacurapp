<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetalleServiciosExtraTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detalle_servicios_extra', function (Blueprint $table) {
            $table->Increments('id');
            $table->integer('cantidad_servicio');
            $table->integer('subtotal');

            // Llaves foráneas
            $table->unsignedInteger('id_consumo');
            $table->foreign('id_consumo')->references('id')->on('consumos')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedInteger('id_servicio_extra');
            $table->foreign('id_servicio_extra')->references('id')->on('servicios')
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
        Schema::dropIfExists('detalle_servicios_extra');
    }
}
