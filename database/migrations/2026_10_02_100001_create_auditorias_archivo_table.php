<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditoriasArchivoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Misma estructura que `auditorias`, conservando el id original.
     * Sin FK a users: el usuario puede haber sido eliminado durante los años de archivo.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auditorias_archivo', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary();

            $table->unsignedInteger('user_id')->nullable();
            $table->string('user_nombre')->nullable();
            $table->string('user_roles')->nullable();
            $table->string('actor_tipo', 20);
            $table->string('actor_detalle')->nullable();

            $table->string('categoria', 20);
            $table->string('evento', 40);
            $table->nullableMorphs('auditable');
            $table->text('descripcion');
            $table->json('cambios')->nullable();

            $table->string('ip', 45)->nullable();
            $table->string('ruta_nombre')->nullable();
            $table->uuid('request_id')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('archivado_at')->nullable();

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
        Schema::dropIfExists('auditorias_archivo');
    }
}
