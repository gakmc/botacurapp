<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePropinasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('propinas', function (Blueprint $table) {
            $table->Increments('id');
            $table->date('fecha');
            $table->decimal('cantidad');
            // Llave foránea
            $table->unsignedInteger('id_consumo');
            $table->timestamps();
            $table->foreign('id_consumo')->references('id')->on('consumos')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('propinas');
    }
}
