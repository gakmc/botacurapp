<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTinajasConfigTable extends Migration
{
    /**
     * Una sola fila (id=1) que guarda si el swap Tinaja 1 <-> Tinaja 2 esta
     * activo. Se controla desde un switch de Home Assistant que llama a
     * POST /api/iot/tinajas/set-inversion.
     */
    public function up()
    {
        if (Schema::hasTable('tinajas_config')) {
            return;
        }

        Schema::create('tinajas_config', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('invertido')->default(false);
            $table->timestamps();
        });

        DB::table('tinajas_config')->insert([
            'id'         => 1,
            'invertido'  => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('tinajas_config');
    }
}
