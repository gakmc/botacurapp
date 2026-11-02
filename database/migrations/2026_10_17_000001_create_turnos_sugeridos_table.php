<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Turnos sugeridos por el reporte del dia siguiente (17/10/2026). */
class CreateTurnosSugeridosTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('turnos_sugeridos')) {
            return;
        }
        Schema::create('turnos_sugeridos', function (Blueprint $table) {
            $table->increments('id');
            $table->date('fecha');
            $table->string('nombre', 120);
            $table->unsignedInteger('user_id')->nullable();
            $table->string('area', 30)->default('atencion');
            $table->timestamp('avisado_at')->nullable();
            $table->timestamps();
            $table->index(['fecha', 'area']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('turnos_sugeridos');
    }
}
