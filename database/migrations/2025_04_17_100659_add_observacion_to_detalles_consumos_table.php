<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddObservacionToDetallesConsumosTable extends Migration
{
    public function up()
    {
        Schema::table('detalles_consumos', function (Blueprint $table) {
            $table->string('observacion')->nullable()->after('estado');
        });
    }

    public function down()
    {
        Schema::table('detalles_consumos', function (Blueprint $table) {
            $table->dropColumn('observacion');
        });
    }
}
