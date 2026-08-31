<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddComprobanteTransferenciaToVentas extends Migration
{
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('comprobante_transferencia')->nullable()->after('estado_pago');
            $table->unsignedBigInteger('verificado_por')->nullable()->after('comprobante_transferencia');
            $table->timestamp('verificado_at')->nullable()->after('verificado_por');
        });
    }

    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['comprobante_transferencia', 'verificado_por', 'verificado_at']);
        });
    }
}
