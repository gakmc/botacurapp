<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddPermiteReprogramacionToProgramasTable extends Migration
{
    public function up()
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->boolean('permite_reprogramacion')->default(true)->after('estado');
        });

        DB::table('programas')
            ->whereIn('nombre_programa', ['Wellness Day', 'Wellness Plus'])
            ->update(['permite_reprogramacion' => false]);
    }

    public function down()
    {
        Schema::table('programas', function (Blueprint $table) {
            $table->dropColumn('permite_reprogramacion');
        });
    }
}
