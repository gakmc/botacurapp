<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEspacioTipoToProgramasTable extends Migration
{
    /**
     * Agrega la columna espacio_tipo a la tabla programas.
     * Define qué tipo de espacio físico ocupa cada programa, lo que
     * determina su capacidad máxima diaria independiente de otros programas.
     *
     * Tipos y cupos máximos:
     *   estacion_economico  → 2 cupos  (estaciones económicas)
     *   estacion_intermedio → 2 cupos  (estaciones intermedias)
     *   estacion_full       → 5 cupos  (estaciones full)
     *   terraza             → 5 cupos  (terrazas grupales)
     *   reposera            → 4 cupos  (pares de reposeras)
     *
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('programas', 'espacio_tipo')) {
            Schema::table('programas', function (Blueprint $table) {
                $table->enum('espacio_tipo', [
                    'estacion_economico',
                    'estacion_intermedio',
                    'estacion_full',
                    'terraza',
                    'reposera',
                ])->nullable()->after('descuento')->comment('Tipo de espacio físico que ocupa el programa');

                $table->index('espacio_tipo', 'idx_programas_espacio_tipo');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->dropIndex('idx_programas_espacio_tipo');
            $table->dropColumn('espacio_tipo');
        });
    }
}
