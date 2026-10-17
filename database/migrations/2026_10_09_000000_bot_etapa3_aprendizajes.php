<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BotEtapa3Aprendizajes extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bot_eventos')) {
            Schema::create('bot_eventos', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('tipo', 40)->index();
                $t->string('telefono', 30)->nullable()->index();
                $t->text('detalle')->nullable();
                $t->timestamp('created_at')->nullable()->index();
            });
        }
        if (!Schema::hasTable('bot_aprendizajes')) {
            Schema::create('bot_aprendizajes', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('tipo', 20)->default('flujo');
                $t->string('titulo', 150);
                $t->text('propuesta');
                $t->text('motivo')->nullable();
                $t->text('evidencia')->nullable();
                $t->string('prioridad', 10)->default('media');
                $t->string('estado', 15)->default('sugerido')->index(); // sugerido | aprobado | descartado
                $t->boolean('activo')->default(false);
                $t->string('origen', 10)->default('ia'); // ia | equipo
                $t->unsignedBigInteger('revisado_por')->nullable();
                $t->timestamp('revisado_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bot_aprendizajes');
        Schema::dropIfExists('bot_eventos');
    }
}
