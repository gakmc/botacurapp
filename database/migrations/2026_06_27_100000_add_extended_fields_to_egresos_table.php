<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtendedFieldsToEgresosTable extends Migration
{
    /**
     * Agrega campos extendidos a la tabla egresos:
     *   - descripcion:       texto libre del gasto (ej: "Compra gas – Proveedor X")
     *   - fecha_egreso:      fecha efectiva del gasto (distinto de fecha de pago)
     *   - numero_documento:  número de factura/boleta/guía
     *   - metodo_pago:       efectivo, transferencia, tarjeta, cheque
     *   - estado:            pendiente | pagado | anulado
     *   - fuente:            manual | home_assistant | woocommerce | scan
     *   - observaciones:     notas adicionales
     *
     * Estas columnas son requeridas por:
     *   - GasIotController (operación pago_proveedor)
     *   - EgresoScanController (scan de facturas con Claude Vision)
     */
    public function up()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->string('descripcion')->nullable()->after('proveedor_id');
            $table->date('fecha_egreso')->nullable()->after('descripcion');
            $table->string('numero_documento', 120)->nullable()->after('fecha_egreso');
            $table->enum('metodo_pago', ['efectivo', 'transferencia', 'tarjeta', 'cheque'])->nullable()->after('numero_documento');
            $table->enum('estado', ['pendiente', 'pagado', 'anulado'])->default('pendiente')->after('metodo_pago');
            $table->enum('fuente', ['manual', 'home_assistant', 'woocommerce', 'scan'])->default('manual')->after('estado');
            $table->text('observaciones')->nullable()->after('fuente');
        });
    }

    public function down()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->dropColumn([
                'descripcion',
                'fecha_egreso',
                'numero_documento',
                'metodo_pago',
                'estado',
                'fuente',
                'observaciones',
            ]);
        });
    }
}
