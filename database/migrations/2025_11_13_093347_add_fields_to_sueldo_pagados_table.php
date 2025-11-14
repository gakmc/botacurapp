<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToSueldoPagadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sueldos_pagados', function (Blueprint $table) {
            $table->integer('bono')->after('monto')->nullable();
            $table->string('motivo')->after('bono')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sueldos_pagados', function (Blueprint $table) {
            $table->dropColumn('bono');
            $table->dropColumn('motivo');
        });
    }
}
