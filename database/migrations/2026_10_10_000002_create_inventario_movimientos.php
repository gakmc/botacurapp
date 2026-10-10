<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Movimientos de inventario (kardex). El stock de un insumo es la suma de
 * inventario_movimiento_detalles.cantidad_base (positiva = entra, negativa = sale),
 * siempre expresada en la unidad del insumo.
 */
class CreateInventarioMovimientos extends Migration
{
    public function up()
    {
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->dateTime('fecha');
            $table->string('tipo', 20);            // entrada | salida | ajuste
            $table->string('origen', 30);          // toma_inicial | toma | compra_factura | compra_manual | consumo | venta_directa | merma | ajuste_manual
            $table->string('referencia_type')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->unsignedBigInteger('id_user')->nullable();
            $table->unsignedBigInteger('id_sector')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->index(['referencia_type', 'referencia_id']);
            $table->index(['origen', 'fecha']);
        });

        Schema::create('inventario_movimiento_detalles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('movimiento_id');
            $table->unsignedBigInteger('id_insumo');
            $table->decimal('cantidad', 14, 4);           // en la unidad ingresada
            $table->unsignedBigInteger('id_unidad_medida')->nullable();
            $table->decimal('cantidad_base', 14, 4);      // en la unidad del insumo, con signo
            $table->decimal('costo_unitario', 14, 4)->default(0); // por unidad del insumo, neto
            $table->decimal('costo_total', 14, 2)->default(0);    // con signo
            $table->timestamps();

            $table->foreign('movimiento_id')->references('id')->on('inventario_movimientos')->onDelete('cascade');
            $table->index('id_insumo');
        });

        // Stock con decimales (0,05 lt de ron, 0,25 kg de mantequilla).
        // insumos.cantidad pasa a ser el stock vigente que mantiene InventarioService.
        DB::statement('ALTER TABLE insumos MODIFY cantidad DECIMAL(14,4) NULL DEFAULT 0, MODIFY stock_critico DECIMAL(14,4) NULL DEFAULT 0');
    }

    public function down()
    {
        DB::statement('ALTER TABLE insumos MODIFY cantidad INT NULL, MODIFY stock_critico INT NULL');
        Schema::dropIfExists('inventario_movimiento_detalles');
        Schema::dropIfExists('inventario_movimientos');
    }
}
