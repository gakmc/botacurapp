<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMinPersonasToProgramasTable extends Migration
{
    public function up()
    {
        Schema::table('programas', function (Blueprint $table) {
            if (! Schema::hasColumn('programas', 'permite_giftcard')) {
                $table->tinyInteger('permite_giftcard')
                    ->default(0)
                    ->after('descuento');
            }

            if (! Schema::hasColumn('programas', 'min_personas')) {
                $table->unsignedTinyInteger('min_personas')
                    ->default(1)
                    ->after('permite_giftcard');
            }

            if (! Schema::hasColumn('programas', 'espacio_tipo')) {
                $table->enum('espacio_tipo', ['estacion_economico', 'estacion_intermedio', 'estacion_full', 'terraza', 'reposera'])
                    ->comment('	Tipo de espacio físico que ocupa el programa')
                    ->nullable()
                    ->after('min_personas');
            }

            if (! Schema::hasColumn('programas', 'solo_plataforma')) {
                $table->tinyInteger('solo_plataforma')
                    ->default(0)
                    ->after('espacio_tipo');
            }

            if (! Schema::hasColumn('programas', 'wc_main_image_ids')) {
                $table->text('wc_main_image_ids')
                    ->nullable()
                    ->after('solo_plataforma');
            }
        });
    }

    public function down()
    {
        Schema::table('programas', function (Blueprint $table) {
            $columns = array_filter(
                ['permite_giftcard', 'min_personas', 'solo_plataforma', 'wc_main_image_ids'],
                function ($col) {return Schema::hasColumn('programas', $col);}
            );
            if (! empty($columns)) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
}
