<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega incluye_masajes e incluye_almuerzos a la tabla programas.
 * Usa hasColumn para no fallar si ya existen en producción.
 */
class AddIncluyeFlagsToProgramasTable extends Migration
{
    public function up()
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

    public function down()
    {
        Schema::table('programas', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('programas', 'incluye_masajes'))   $cols[] = 'incluye_masajes';
            if (Schema::hasColumn('programas', 'incluye_almuerzos')) $cols[] = 'incluye_almuerzos';
            if ($cols) $table->dropColumn($cols);
        });
    }
}
