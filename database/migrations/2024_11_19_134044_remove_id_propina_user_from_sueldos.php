<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveIdPropinaUserFromSueldos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sueldos', function (Blueprint $table) {
            if (Schema::hasColumn('sueldos', 'id_propina_user')) {
                $table->dropForeign(['id_propina_user']); // Eliminar la clave foránea, si existe
                $table->dropColumn('id_propina_user');   // Eliminar la columna
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sueldos', function (Blueprint $table) {
            //
        });
    }
}
