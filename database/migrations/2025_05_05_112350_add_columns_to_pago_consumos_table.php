<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToPagoConsumosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Paso 1: Eliminar claves foráneas existentes
        Schema::table('pago_consumos', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_transaccion']);
            $table->dropForeign(['id_consumo']);
        });

        // Paso 2: Crear nuevas columnas
        Schema::table('pago_consumos', function (Blueprint $table) {
            // Nuevas versiones de las columnas
            $table->string('imagen_pago1')->nullable()->after('valor_consumo');
            $table->unsignedInteger('id_tipo_transaccion1')->nullable()->after('imagen_pago1');
            $table->unsignedInteger('id_venta')->after('id_tipo_transaccion1');

            // Nuevas columnas adicionales
            $table->integer('pago1')->after('valor_consumo');
            $table->integer('pago2')->nullable()->after('pago1');
            $table->string('imagen_pago2')->nullable()->after('imagen_pago1');
            $table->unsignedInteger('id_tipo_transaccion2')->nullable()->after('id_tipo_transaccion1');
        });

        // Paso 3: Eliminar columnas antiguas
        Schema::table('pago_consumos', function (Blueprint $table) {
            $table->dropColumn(['imagen_transaccion', 'id_tipo_transaccion', 'id_consumo']);
        });

        // Paso 4: Agregar nuevas claves foráneas
        Schema::table('pago_consumos', function (Blueprint $table) {
            $table->foreign('id_tipo_transaccion1')->references('id')->on('tipos_transacciones')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_tipo_transaccion2')->references('id')->on('tipos_transacciones')->onDelete('set null')->onUpdate('cascade');
            $table->foreign('id_venta')->references('id')->on('ventas')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Paso 1: Eliminar claves foráneas nuevas
        Schema::table('pago_consumos', function (Blueprint $table) {
            $table->dropForeign(['id_tipo_transaccion1']);
            $table->dropForeign(['id_tipo_transaccion2']);
            $table->dropForeign(['id_venta']);
        });

        // Paso 2: Eliminar columnas nuevas
        Schema::table('pago_consumos', function (Blueprint $table) {
            $table->dropColumn([
                'pago1', 'pago2', 'imagen_pago2',
                'imagen_pago1', 'id_tipo_transaccion1',
                'id_tipo_transaccion2', 'id_venta'
            ]);
        });

        // Paso 3: Restaurar columnas originales
        Schema::table('pago_consumos', function (Blueprint $table) {
            $table->string('imagen_transaccion')->nullable()->after('valor_consumo');
            $table->unsignedInteger('id_tipo_transaccion')->nullable()->after('imagen_transaccion');
            $table->unsignedInteger('id_consumo')->nullable()->after('id_tipo_transaccion');

            $table->foreign('id_tipo_transaccion')->references('id')->on('tipos_transacciones')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_consumo')->references('id')->on('consumos')->onDelete('cascade')->onUpdate('cascade');
        });
    }
}
