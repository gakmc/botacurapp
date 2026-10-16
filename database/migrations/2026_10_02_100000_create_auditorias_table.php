<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditoriasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Actor (snapshot al momento de la acción)
            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_nombre')->nullable();
            $table->string('user_roles')->nullable();
            $table->string('actor_tipo', 20);
            $table->string('actor_detalle')->nullable();

            // Qué ocurrió
            $table->string('categoria', 20);
            $table->string('evento', 40);
            $table->nullableMorphs('auditable');
            $table->text('descripcion');
            $table->json('cambios')->nullable();

            // Contexto
            $table->string('ip', 45)->nullable();
            $table->string('ruta_nombre')->nullable();
            $table->uuid('request_id')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');

            $table->index(['user_id', 'created_at']);
            $table->index(['categoria', 'created_at']);
            $table->index('evento');
            $table->index('created_at');
            $table->index('request_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auditorias');
    }
}
