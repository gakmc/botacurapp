<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ciclo de compras de insumos: lista de compras -> pedido por proveedor ->
 * compra (quién compró qué) -> recepción verificada (quién contó lo que llegó).
 * Reemplaza la planilla donde se borraban las filas al comprar.
 */
class CreatePedidosCompra extends Migration
{
    public function up()
    {
        Schema::table('insumos', function (Blueprint $table) {
            $table->unsignedInteger('id_proveedor')->nullable()->after('id_sector');   // proveedor habitual (sección de compra)
            $table->string('ubicacion', 80)->nullable()->after('id_proveedor');        // Equipo 3, Despensa 1, Bodega aseo…
            $table->decimal('stock_objetivo', 14, 4)->nullable()->after('stock_critico'); // hasta dónde reponer
            $table->string('presentacion', 80)->nullable()->after('stock_objetivo');   // cómo se compra: caja 12, saco 25 kg…
        });

        Schema::create('pedidos_compra', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('id_proveedor')->nullable();
            $table->string('estado', 20)->default('por_comprar'); // por_comprar | comprado | recibido | con_diferencias | cancelado
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('comprado_por')->nullable();
            $table->dateTime('comprado_at')->nullable();
            $table->unsignedBigInteger('recibido_por')->nullable();
            $table->dateTime('recibido_at')->nullable();
            $table->unsignedInteger('egreso_id')->nullable();          // factura SII asociada
            $table->unsignedBigInteger('movimiento_id')->nullable();   // entrada de inventario generada
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->index(['estado', 'id_proveedor']);
        });

        Schema::create('pedido_compra_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('pedido_id');
            $table->unsignedInteger('id_insumo');
            $table->decimal('cantidad_pedida', 14, 4)->default(0);
            $table->decimal('cantidad_comprada', 14, 4)->nullable();
            $table->decimal('costo_total_neto', 14, 2)->nullable();
            $table->decimal('cantidad_recibida', 14, 4)->nullable();
            // pendiente | comprado | no_habia | ok | faltante | sobrante | no_llego | extra (llegó sin pedirse)
            $table->string('estado', 20)->default('pendiente');
            $table->string('nota', 255)->nullable();
            $table->timestamps();
            $table->foreign('pedido_id')->references('id')->on('pedidos_compra')->onDelete('cascade');
            $table->index('id_insumo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pedido_compra_items');
        Schema::dropIfExists('pedidos_compra');
        Schema::table('insumos', function (Blueprint $table) {
            $table->dropColumn(['id_proveedor', 'ubicacion', 'stock_objetivo', 'presentacion']);
        });
    }
}
