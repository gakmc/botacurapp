<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTiempoExtraToMasajesTable extends Migration
{
    public function up()
    {
        Schema::table('masajes', function (Blueprint $table) {
            $table->boolean('tiempo_extra')->default(false)->after('persona');
        });
    }

    public function down()
    {
        Schema::table('masajes', function (Blueprint $table) {
            
            $table->dropColumn('tiempo_extra');
          
        });
    }
}
