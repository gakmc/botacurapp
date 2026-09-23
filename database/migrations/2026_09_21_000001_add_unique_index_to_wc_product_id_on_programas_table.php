<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * La migración original (2026_04_27_165547) define wc_product_id como unique(),
 * pero en producción la columna se creó a mano vía SQL sin esa restricción
 * (migracion_produccion_2026_07_22.sql no incluye UNIQUE). Esta migración
 * agrega el índice solo si todavía no existe, para no romper en ambientes
 * donde sí se creó correctamente.
 */
class AddUniqueIndexToWcProductIdOnProgramasTable extends Migration
{
    public function up()
    {
        if (!$this->indexExists()) {
            Schema::table('programas', function ($table) {
                $table->unique('wc_product_id', 'programas_wc_product_id_unique');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists()) {
            Schema::table('programas', function ($table) {
                $table->dropUnique('programas_wc_product_id_unique');
            });
        }
    }

    private function indexExists(): bool
    {
        $database = DB::getDatabaseName();

        $count = DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', 'programas')
            ->where('column_name', 'wc_product_id')
            ->where('non_unique', 0)
            ->count();

        return $count > 0;
    }
}
