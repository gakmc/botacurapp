<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSueldosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sueldos', function (Blueprint $table) {
            $table->Increments('id');
            $table->date('dia_trabajado');
            $table->integer('valor_dia');
            $table->integer('sub_sueldo')->nullable();
            $table->integer('total_pagar')->nullable();

            $table->unsignedInteger('id_user');
            $table->foreign('id_user')->references('id')->on('users')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->unsignedInteger('id_propina_user'); // Nueva referencia a la tabla intermedia
            $table->foreign('id_propina_user')->references('id')->on('propina_user')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sueldos');
    }
}
