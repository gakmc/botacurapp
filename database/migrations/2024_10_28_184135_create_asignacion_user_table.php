<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsignacionUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asignacion_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('asignacion_id'); // Verifica que coincide con el tipo de 'id' en la tabla 'asignaciones'
            $table->unsignedInteger('user_id'); // Verifica que coincide con el tipo de 'id' en la tabla 'users'
            $table->timestamps();
    
            // Definir las llaves foráneas
            $table->foreign('asignacion_id')->references('id')->on('asignaciones')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asignacion_user');
    }
}
