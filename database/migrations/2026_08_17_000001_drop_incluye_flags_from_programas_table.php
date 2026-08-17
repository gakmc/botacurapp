<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Elimina incluye_masajes e incluye_almuerzos de la tabla programas.
 *
 * Estas columnas quedaron redundantes: el modelo Programa ya deriva
 * ambos valores en vivo desde la relación programa_servicio -> servicios
 * (ver Programa::getIncluyeMasajesAttribute / getIncluyeAlmuerzosAttribute),
 * comparando el nombre del servicio asociado ("Masaje", "Almuerzo", etc).
 * La tabla pivote programa_servicio + servicios es la fuente de verdad.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class DropIncluyeFlagsFromProgramasTable extends Migration
{
    public function up()
    {
        Schema::table('programas', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('programas', 'incluye_masajes'))   $cols[] = 'incluye_masajes';
            if (Schema::hasColumn('programas', 'incluye_almuerzos')) $cols[] = 'incluye_almuerzos';
            if ($cols) $table->dropColumn($cols);
        });
    }

    public function down()
    {
        Schema::table('programas', function (Blueprint $table) {
            if (!Schema::hasColumn('programas', 'incluye_masajes')) {
                $table->boolean('incluye_masajes')->default(false)->after('valor_programa');
            }
            if (!Schema::hasColumn('programas', 'incluye_almuerzos')) {
                $table->boolean('incluye_almuerzos')->default(false)->after('incluye_masajes');
            }
        });
    }
}
