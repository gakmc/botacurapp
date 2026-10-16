<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditoriaAutorizadosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Quién puede ver la auditoría. Tabla propia (no un Permission) porque
     * User::verify_permission_integrity() quita los permisos ajenos a los roles.
     *
     * nivel: 'gestor' (ve y administra accesos; lo otorga la consola u otro gestor)
     *        'lector' (solo ve; lo otorga un gestor desde la plataforma)
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auditoria_autorizados', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->string('nivel', 10);
            $table->unsignedInteger('otorgado_por')->nullable();
            $table->timestamp('otorgado_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('otorgado_por')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auditoria_autorizados');
    }
}
