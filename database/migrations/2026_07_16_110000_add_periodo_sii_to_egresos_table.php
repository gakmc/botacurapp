<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega periodo_sii (YYYY-MM) a egresos.
 *
 * El RCV del SII incluye facturas cuya fecha de emisión puede ser del mes
 * anterior (ej: Enero 2026 trae facturas fechadas en Diciembre 2025).
 * Este campo guarda el PERIODO DE IMPORTACIÓN SII (no la fecha del doc),
 * para poder filtrar correctamente por mes/año en resumen y detalle.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class AddPeriodoSiiToEgresosTable extends Migration
{
    public function up()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->string('periodo_sii', 7)->nullable()->comment('Período SII de importación en formato YYYY-MM (ej: 2026-01)');
        });
    }

    public function down()
    {
        Schema::table('egresos', function (Blueprint $table) {
            $table->dropColumn('periodo_sii');
        });
    }
}
