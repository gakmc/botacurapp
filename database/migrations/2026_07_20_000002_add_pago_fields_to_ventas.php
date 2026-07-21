<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega campos de pago a la tabla ventas.
 *
 * - referencia_transferencia : código único BTC-{id}-{YYMMDD}, se muestra al cliente
 *                              para que lo ingrese en el comentario de la transferencia.
 * - webpay_token             : token de sesión Webpay Plus (init → commit).
 * - webpay_orden             : buy_order enviado a Transbank.
 * - metodo_pago              : 'transferencia' | 'webpay'
 * - monto_pagado             : monto efectivamente confirmado (abono 50% o 100%).
 * - estado_pago              : pendiente | abono_recibido | pago_completo | fallido
 * - confirmado_en            : timestamp cuando se verificó el pago.
 */
class AddPagoFieldsToVentas extends Migration
{
    public function up()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('referencia_transferencia', 30)->nullable()->unique()->after('total_pagar');
            $table->string('webpay_token', 100)->nullable()->after('referencia_transferencia');
            $table->string('webpay_orden', 30)->nullable()->after('webpay_token');
            $table->string('metodo_pago', 20)->nullable()->after('webpay_orden');
            $table->integer('monto_pagado')->nullable()->after('metodo_pago');
            $table->string('estado_pago', 20)->nullable()->default('pendiente')->after('monto_pagado');
            $table->timestamp('confirmado_en')->nullable()->after('estado_pago');
        });
    }

    public function down()
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique(['referencia_transferencia']);
            $table->dropColumn([
                'referencia_transferencia',
                'webpay_token',
                'webpay_orden',
                'metodo_pago',
                'monto_pagado',
                'estado_pago',
                'confirmado_en',
            ]);
        });
    }
}
