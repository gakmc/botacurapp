<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToMasajesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('masajes', function (Blueprint $table) {
            $table->time('horario_masaje')->nullable()->after('id');
            $table->string('tipo_masaje')->nullable()->after('horario_masaje');
            $table->unsignedInteger('id_lugar_masaje')->nullable()->after('tipo_masaje');

            $table->unsignedInteger('user_id')->nullable()->change();

            $table->foreign('id_lugar_masaje')->references('id')->on('lugares_masajes')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('masajes', function (Blueprint $table) {
            $table->dropForeign('masajes_id_lugar_masaje_foreign');
            $table->dropColumn(['horario_masaje', 'tipo_masaje', 'id_lugar_masaje']);
        });
    }
}
