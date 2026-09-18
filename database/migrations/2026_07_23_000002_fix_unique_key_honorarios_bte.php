<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixUniqueKeyHonorariosBte extends Migration
{
    public function up()
    {
        Schema::table('honorarios_bte', function (Blueprint $table) {
            // Folio solo no es único — distintos emisores pueden tener el mismo folio
            $table->dropUnique('honorarios_bte_folio_unique');
            $table->unique(['folio', 'rut_emisor'], 'honorarios_bte_folio_emisor_unique');
        });
    }

    public function down()
    {
        Schema::table('honorarios_bte', function (Blueprint $table) {
            $table->dropUnique('honorarios_bte_folio_emisor_unique');
            $table->unique('folio', 'honorarios_bte_folio_unique');
        });
    }
}
