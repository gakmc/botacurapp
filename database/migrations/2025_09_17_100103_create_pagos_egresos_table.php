<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePagosEgresosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagos_egresos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('egreso_id');
            $table->string('folio')->nullable();
            $table->integer('monto');
            $table->integer('neto')->nullable();
            $table->integer('iva')->nullable();
            $table->integer('impuesto_incluido')->nullable();
            $table->date('fecha_pago');
            $table->timestamps();

            $table->foreign('egreso_id')->references('id')->on('egresos')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagos_egresos');
    }
}
