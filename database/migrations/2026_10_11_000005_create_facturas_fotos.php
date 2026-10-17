<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fotos de facturas impresas (proveedores que no envían el XML, como Cocha):
 * se leen con IA, se corroboran contra el registro de compras del SII y, al
 * confirmarse, sus líneas pasan a compra_items (origen = foto).
 */
class CreateFacturasFotos extends Migration
{
    public function up()
    {
        Schema::create('facturas_fotos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->json('archivos');                         // rutas en storage/app/facturas_fotos/
            $table->json('extraccion')->nullable();           // lo que leyó la IA (y lo corregido al revisar)
            $table->json('verificacion')->nullable();         // comparación con el SII
            $table->unsignedInteger('egreso_id')->nullable(); // factura del registro de compras
            $table->unsignedBigInteger('pedido_id')->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente | confirmada | descartada | error
            $table->text('error')->nullable();
            $table->unsignedBigInteger('subida_por')->nullable();
            $table->unsignedBigInteger('confirmada_por')->nullable();
            $table->timestamps();
            $table->index(['estado', 'created_at']);
        });

        Schema::table('compra_items', function (Blueprint $table) {
            $table->string('origen', 10)->default('xml')->after('id_insumo');  // xml | foto
            $table->unsignedBigInteger('factura_foto_id')->nullable()->after('origen');
        });
    }

    public function down()
    {
        Schema::table('compra_items', function (Blueprint $table) {
            $table->dropColumn(['origen', 'factura_foto_id']);
        });
        Schema::dropIfExists('facturas_fotos');
    }
}
