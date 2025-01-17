<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveFieldsFromVisitasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visitas', function (Blueprint $table) {
            $table->dropForeign('visitas_id_lugar_masaje_foreign');
            $table->dropColumn(['horario_masaje', 'tipo_masaje', 'id_lugar_masaje']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitas', function (Blueprint $table) {

            $table->time('horario_masaje')->nullable();
            $table->string('tipo_masaje')->nullable();
            $table->unsignedInteger('id_lugar_masaje')->nullable();
            $table->foreign('id_lugar_masaje')->references('id')->on('lugares_masajes')->onUpdate('cascade')->onDelete('cascade');

        });
    }
}
