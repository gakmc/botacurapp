<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddDatosExtraidosComprobanteToVentas extends Migration
{
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->integer('comprobante_monto')->nullable()->after('comprobante_transferencia');
            $table->date('comprobante_fecha')->nullable()->after('comprobante_monto');
            $table->string('comprobante_hora', 10)->nullable()->after('comprobante_fecha');
            $table->string('comprobante_numero_operacion', 100)->nullable()->after('comprobante_hora');
            $table->string('comprobante_nombre_origen', 200)->nullable()->after('comprobante_numero_operacion');
            $table->string('comprobante_tipo_detectado', 30)->nullable()->after('comprobante_nombre_origen');
            $table->string('comprobante_alerta', 255)->nullable()->after('comprobante_tipo_detectado');
        });
    }

    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn([
                'comprobante_monto', 'comprobante_fecha', 'comprobante_hora',
                'comprobante_numero_operacion', 'comprobante_nombre_origen',
                'comprobante_tipo_detectado', 'comprobante_alerta',
            ]);
        });
    }
}
