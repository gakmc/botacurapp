<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddContextoToAuditoriasTables extends Migration
{
    /**
     * Run the migrations.
     *
     * contexto: registro "padre" que agrupa la acción (ej. la Reserva de un pedido,
     * un masaje o la venta). Permite armar el historial completo de una reserva,
     * incluso de registros hijos que ya fueron eliminados.
     *
     * @return void
     */
    public function up()
    {
        foreach (['auditorias', 'auditorias_archivo'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->nullableMorphs('contexto');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (['auditorias', 'auditorias_archivo'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropMorphs('contexto');
            });
        }
    }
}
