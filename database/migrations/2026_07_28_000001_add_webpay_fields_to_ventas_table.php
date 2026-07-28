<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebpayFieldsToVentasTable extends Migration
{
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('webpay_token', 64)->nullable()->after('id_tipo_transaccion_diferencia');
            $table->string('webpay_url', 500)->nullable()->after('webpay_token');
            $table->string('estado_pago', 30)->nullable()->default('pendiente')->after('webpay_url');
            // pendiente | pagado | rechazado | anulado
        });
    }

    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['webpay_token', 'webpay_url', 'estado_pago']);
        });
    }
}
