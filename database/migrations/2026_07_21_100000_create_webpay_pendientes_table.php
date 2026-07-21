<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla temporal para reservas bot WhatsApp pendientes de pago.
 * Se crea un registro al generar el link Webpay y se elimina
 * al confirmar o rechazar el pago.
 */
class CreateWebpayPendientesTable extends Migration
{
    public function up()
    {
        Schema::create('webpay_pendientes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('webpay_token', 64)->unique();
            $table->string('webpay_orden', 26);
            $table->unsignedInteger('monto');
            $table->text('datos_json'); // JSON con todos los datos de la reserva
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webpay_pendientes');
    }
}
