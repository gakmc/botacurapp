<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFechaDisponiblesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fecha_disponibles', function (Blueprint $table) {
            $table->Increments('id');
            $table->date('fecha')->unique();
            $table->enum('tipo', ['regular', 'festivo', 'especial'])->default('regular');
            $table->boolean('habilitada')->default(true);
            $table->string('nota')->nullable(); // ej: "Feriado nacional"
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
        Schema::dropIfExists('fecha_disponibles');
    }
}
