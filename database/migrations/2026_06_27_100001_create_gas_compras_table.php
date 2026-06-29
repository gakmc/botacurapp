<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGasComprasTable extends Migration
{
    /**
     * Tabla gas_compras (BD principal: botacurapp / conexión mysql).
     *
     * Registra cada compra/pago de cilindros de gas al proveedor.
     * Se vincula con un egreso en la tabla egresos para el registro contable.
     *
     * Modelo: App\Models\GasCompra
     * Usado por: GasIotController (operación pago_proveedor)
     */
    public function up()
    {
        Schema::create('gas_compras', function (Blueprint $table) {
            $table->increments('id');

            // Proveedor (puede ser FK o texto libre desde HA)
            $table->unsignedInteger('proveedor_id')->nullable();
            $table->string('proveedor_nombre', 150)->nullable();

            // Datos de la compra
            $table->date('fecha_compra');
            $table->integer('valor_unitario_clp');
            $table->integer('cantidad_cilindros')->default(1);
            $table->decimal('kg_cilindro', 5, 2)->nullable()->comment('Kg por cilindro, ej: 15.00');
            $table->integer('total_clp')->comment('Calculado: valor_unitario * cantidad (auto en modelo)');

            // Documento de respaldo
            $table->string('documento', 120)->nullable()->comment('Número de factura / boleta');

            // Vínculo contable con egresos
            $table->unsignedInteger('egreso_id')->nullable();

            // Trazabilidad
            $table->enum('origen', ['home_assistant', 'manual'])->default('manual');
            $table->enum('estado', ['comprado', 'anulado'])->default('comprado');
            $table->text('observacion')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('proveedor_id')
                  ->references('id')->on('proveedores')
                  ->onUpdate('cascade')->onDelete('set null');

            $table->foreign('egreso_id')
                  ->references('id')->on('egresos')
                  ->onUpdate('cascade')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gas_compras');
    }
}
