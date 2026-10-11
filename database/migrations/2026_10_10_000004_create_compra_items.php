<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Detalle (productos) de las facturas recibidas, leído del XML del DTE en el
 * portal MIPYME del SII. Base del catálogo de insumos por proveedor.
 */
class CreateCompraItems extends Migration
{
    public function up()
    {
        Schema::create('compra_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('egreso_id')->nullable();   // egreso SII del mismo documento
            $table->string('rut_emisor', 15);
            $table->string('razon_social')->nullable();
            $table->unsignedSmallInteger('dte');
            $table->string('folio', 20);
            $table->date('fecha');
            $table->unsignedSmallInteger('linea');
            $table->string('codigo', 60)->nullable();
            $table->string('nombre');
            $table->decimal('cantidad', 14, 4)->nullable();
            $table->string('unidad', 20)->nullable();
            $table->decimal('precio', 14, 4)->nullable();
            $table->decimal('monto', 14, 2)->default(0);        // negativo en notas de crédito
            $table->unsignedInteger('id_insumo')->nullable();    // asociación al catálogo
            $table->timestamps();

            $table->unique(['rut_emisor', 'dte', 'folio', 'linea'], 'compra_items_doc_linea');
            $table->index(['rut_emisor', 'codigo']);
            $table->index('id_insumo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('compra_items');
    }
}
