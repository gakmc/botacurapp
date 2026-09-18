<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTipoCapacidadToUbicacionesTable extends Migration
{
    public function up()
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            if (!Schema::hasColumn('ubicaciones', 'espacio_tipo')) {
                $table->enum('espacio_tipo', ['estacion', 'wellness'])
                    ->nullable()
                    ->after('nombre')
                    ->comment('Categoría física amplia de la ubicación');
            }

            if (!Schema::hasColumn('ubicaciones', 'sub_tipo')) {
                $table->enum('sub_tipo', ['estacion_normal', 'estacion_grupal', 'terraza', 'reposera'])
                    ->nullable()
                    ->after('espacio_tipo')
                    ->comment('Sub-clasificación física dentro de espacio_tipo');
            }

            if (!Schema::hasColumn('ubicaciones', 'capacidad_min')) {
                $table->unsignedTinyInteger('capacidad_min')->nullable()->after('sub_tipo');
            }

            if (!Schema::hasColumn('ubicaciones', 'capacidad_max')) {
                $table->unsignedTinyInteger('capacidad_max')->nullable()->after('capacidad_min');
            }

            if (!Schema::hasColumn('ubicaciones', 'tiene_terraza')) {
                $table->boolean('tiene_terraza')->default(false)->after('capacidad_max');
            }

            if (!Schema::hasColumn('ubicaciones', 'activo')) {
                $table->boolean('activo')->default(true)->after('tiene_terraza');
            }
        });
    }

    public function down()
    {
        Schema::table('ubicaciones', function (Blueprint $table) {
            $columns = array_filter(
                ['espacio_tipo', 'sub_tipo', 'capacidad_min', 'capacidad_max', 'tiene_terraza', 'activo'],
                function ($col) { return Schema::hasColumn('ubicaciones', $col); }
            );

            if (!empty($columns)) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
}
