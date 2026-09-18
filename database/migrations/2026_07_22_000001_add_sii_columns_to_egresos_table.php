<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega columnas SII a la tabla egresos.
 *
 * Estas columnas son necesarias para el módulo de importación SII (RCV):
 *   descripcion      → razón social + folio del documento
 *   fecha_egreso     → fecha del documento emitido
 *   numero_documento → folio del documento (para deduplicación)
 *   neto             → monto neto del documento
 *   iva              → IVA del documento (para crédito fiscal F29)
 *   fuente           → origen del egreso: 'sii' | 'manual' | 'gas_iot'
 *   estado           → 'pendiente' | 'pagado' | 'anulado'
 *   observaciones    → notas internas
 *
 * El campo periodo_sii fue agregado en migración anterior (2026_07_16).
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class AddSiiColumnsToEgresosTable extends Migration
{
    public function up()
    {
        Schema::table('egresos', function (Blueprint $table) {
            if (!Schema::hasColumn('egresos', 'descripcion')) {
                $table->string('descripcion')->nullable()->after('proveedor_id');
            }
            if (!Schema::hasColumn('egresos', 'fecha_egreso')) {
                $table->date('fecha_egreso')->nullable()->after('descripcion');
            }
            if (!Schema::hasColumn('egresos', 'numero_documento')) {
                $table->string('numero_documento', 50)->nullable()->after('fecha_egreso');
            }
            if (!Schema::hasColumn('egresos', 'neto')) {
                $table->integer('neto')->nullable()->after('total');
            }
            if (!Schema::hasColumn('egresos', 'iva')) {
                $table->integer('iva')->nullable()->after('neto');
            }
            if (!Schema::hasColumn('egresos', 'fuente')) {
                $table->string('fuente', 30)->nullable()->default('manual')
                      ->comment('sii | manual | gas_iot')->after('iva');
            }
            if (!Schema::hasColumn('egresos', 'estado')) {
                $table->string('estado', 20)->nullable()->default('pendiente')
                      ->comment('pendiente | pagado | anulado')->after('fuente');
            }
            if (!Schema::hasColumn('egresos', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('estado');
            }
        });
    }

    public function down()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $cols = ['descripcion', 'fecha_egreso', 'numero_documento', 'neto', 'iva', 'fuente', 'estado', 'observaciones'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('egresos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
