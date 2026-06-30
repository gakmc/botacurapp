<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: honorarios_bte
 *
 * Almacena Boletas de Prestación de Servicios de Terceros Electrónicas (BTE)
 * recibidas por la empresa como receptora, obtenidas desde SII mediante scraping
 * del portal zeus.sii.cl/cvc_cgi/bte/bte_indiv_cons2.
 *
 * Tasa retención 2026: 15.25% (Ley 21.133)
 */
class CreateHonorariosBteTable extends Migration
{
    public function up()
    {
        Schema::dropIfExists('honorarios_bte');
        Schema::create('honorarios_bte', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Identificación del documento
            $table->string('folio', 20)->comment('N° BTE asignado por SII');
            $table->string('periodo', 6)->comment('YYYYMM — mes del informe consultado');
            $table->string('estado', 30)->nullable()->comment('Vigente, Anulada, etc.');

            // Emisor (trabajador independiente)
            $table->string('rut_emisor', 15)->comment('RUT del prestador independiente');
            $table->string('nombre_emisor', 150)->nullable();

            // Fechas
            $table->date('fecha_emision')->comment('Fecha de emisión de la BTE');
            $table->date('fecha_pago')->nullable()->comment('Fecha de pago al emisor');

            // Montos (en pesos chilenos, enteros)
            $table->unsignedInteger('monto_bruto')->default(0)->comment('Honorarios brutos');
            $table->decimal('tasa_retencion', 5, 2)->default(15.25)->comment('% retenido (varía por año)');
            $table->unsignedInteger('monto_retenido')->default(0)->comment('Monto retenido (impuesto)');
            $table->unsignedInteger('monto_pagado')->default(0)->comment('Monto neto pagado al emisor');

            // Relaciones opcionales
            $table->unsignedBigInteger('proveedor_id')->nullable()->comment('FK a proveedores si existe match por RUT');
            $table->unsignedBigInteger('egreso_id')->nullable()->comment('FK a egresos si se importó como gasto');

            // Control
            $table->timestamp('sincronizado_at')->nullable()->comment('Cuándo fue sincronizado desde SII');
            $table->timestamps();

            // Índices
            $table->unique(['folio', 'rut_emisor'], 'uk_folio_emisor');
            $table->index('periodo');
            $table->index('rut_emisor');
            $table->index('fecha_emision');
            $table->index('estado');
        });
    }

    public function down()
    {
        Schema::dropIfExists('honorarios_bte');
    }
}
