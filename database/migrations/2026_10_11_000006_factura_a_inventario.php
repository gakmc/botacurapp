<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pasar productos de facturas al inventario:
 *  - compra_producto_insumo: recuerda a qué insumo corresponde cada producto
 *    de cada proveedor y su conversión (ej. "PAPA MALLA 2KGS" x1 = 2 kg Papas).
 *  - compra_items: qué líneas ya pasaron (o se omitieron) y con qué movimiento.
 *  - Costo con IVA incluido junto al neto, para costear productos y servicios
 *    contra precios de venta que incluyen IVA.
 */
class FacturaAInventario extends Migration
{
    public function up()
    {
        Schema::create('compra_producto_insumo', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('rut_emisor', 15);
            $table->string('clave', 191);                 // código del proveedor, o nombre normalizado si no hay código
            $table->string('nombre');
            $table->unsignedInteger('id_insumo');
            $table->decimal('factor', 14, 6)->default(1); // unidades del insumo por cada unidad de la factura
            $table->timestamps();
            $table->unique(['rut_emisor', 'clave']);
        });

        Schema::table('compra_items', function (Blueprint $table) {
            $table->string('inventario', 10)->nullable()->after('factura_foto_id');      // agregado | omitido
            $table->decimal('cantidad_inventario', 14, 4)->nullable()->after('inventario');
            $table->unsignedBigInteger('movimiento_id')->nullable()->after('cantidad_inventario');
        });

        Schema::table('inventario_movimiento_detalles', function (Blueprint $table) {
            $table->decimal('costo_unitario_iva', 14, 4)->nullable()->after('costo_unitario');
        });

        Schema::table('insumos', function (Blueprint $table) {
            $table->integer('valor_con_iva')->nullable()->after('valor'); // costo promedio con IVA, por unidad del insumo
        });
    }

    public function down()
    {
        Schema::table('insumos', function (Blueprint $table) { $table->dropColumn('valor_con_iva'); });
        Schema::table('inventario_movimiento_detalles', function (Blueprint $table) { $table->dropColumn('costo_unitario_iva'); });
        Schema::table('compra_items', function (Blueprint $table) { $table->dropColumn(['inventario', 'cantidad_inventario', 'movimiento_id']); });
        Schema::dropIfExists('compra_producto_insumo');
    }
}
