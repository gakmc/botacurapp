<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSiiResumenMensualTable extends Migration
{
    /**
     * Acumulador tributario mensual extraído del SII.
     *
     * Almacena los totales de compras y ventas por período (YYYYMM),
     * más los ítems de honorarios (BHE) y su descuento.
     *
     * Se actualiza cada vez que el usuario sincroniza el RCV del mes.
     */
    public function up()
    {
        Schema::create('sii_resumen_mensual', function (Blueprint $table) {
            $table->increments('id');
            $table->char('periodo', 6)->comment('Formato YYYYMM, ej: 202606');

            // ── Compras ───────────────────────────────────────────────────────
            $table->bigInteger('compras_neto')->default(0);
            $table->bigInteger('compras_iva')->default(0);
            $table->bigInteger('compras_exento')->default(0);
            $table->bigInteger('compras_total')->default(0);
            $table->integer('compras_cantidad')->default(0)->comment('Cantidad de documentos');

            // ── Ventas ────────────────────────────────────────────────────────
            $table->bigInteger('ventas_neto')->default(0);
            $table->bigInteger('ventas_iva')->default(0);
            $table->bigInteger('ventas_exento')->default(0);
            $table->bigInteger('ventas_total')->default(0);
            $table->integer('ventas_cantidad')->default(0);

            // ── Honorarios (BHE recibidas) ────────────────────────────────────
            $table->bigInteger('honorarios_bruto')->default(0)->comment('Total boletas honorarios recibidas');
            $table->bigInteger('honorarios_retencion')->default(0)->comment('10.75% retención provisional');
            $table->bigInteger('honorarios_neto')->default(0)->comment('Monto neto pagado');

            // ── IVA acumulado (para F29) ──────────────────────────────────────
            $table->bigInteger('iva_debito')->default(0)->comment('IVA ventas (débito fiscal)');
            $table->bigInteger('iva_credito')->default(0)->comment('IVA compras (crédito fiscal)');
            $table->bigInteger('iva_diferencia')->default(0)->comment('Débito - Crédito = a pagar');

            // ── Metadata ──────────────────────────────────────────────────────
            $table->timestamp('ultima_sincronizacion')->nullable();
            $table->timestamps();

            $table->unique('periodo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sii_resumen_mensual');
    }
}
