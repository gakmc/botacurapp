<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservaHoldsTable extends Migration
{
    public function up()
    {
        Schema::create('reserva_holds', function (Blueprint $table) {
            $table->increments('id');
            $table->string('telefono', 20);
            $table->string('nombre', 200);
            $table->string('email', 200);
            $table->unsignedInteger('programa_id');
            $table->date('fecha');
            $table->unsignedInteger('personas');
            $table->unsignedInteger('masajes_extra')->default(0);
            $table->unsignedInteger('desayuno_once')->default(0);
            $table->string('desayuno_tipo', 20)->nullable();
            $table->string('observacion', 500)->nullable();
            $table->string('tipo_pago', 80)->nullable();
            $table->unsignedInteger('valor_total')->default(0);
            $table->unsignedInteger('abono_50')->default(0);
            $table->unsignedInteger('diferencia')->default(0);
            // activo | confirmado | expirado | cancelado
            $table->string('estado', 20)->default('activo');
            $table->timestamp('expira_en');
            $table->unsignedInteger('id_reserva')->nullable();
            $table->longText('datos_json')->nullable();
            $table->timestamps();

            $table->index(['telefono', 'estado']);
            $table->index(['fecha', 'estado']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reserva_holds');
    }
}
