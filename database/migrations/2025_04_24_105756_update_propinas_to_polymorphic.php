<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePropinasToPolymorphic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('propinas', function (Blueprint $table) {
            // 1. Eliminar la FK si existiera (solo si la tenías declarada)
            if (Schema::hasColumn('propinas', 'id_consumo')) {
                $table->dropForeign(['id_consumo']); // por si hay FK
                $table->dropColumn('id_consumo');
            }

            // 2. Agregar campos polimórficos
            $table->unsignedInteger('propinable_id')->nullable()->after('cantidad');
            $table->string('propinable_type')->nullable()->after('propinable_id');

            // 3. (Opcional) índice combinado para performance
            $table->index(['propinable_type', 'propinable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('propinas', function (Blueprint $table) {
            // Eliminar columnas polimórficas
            $table->dropIndex(['propinable_type', 'propinable_id']);
            $table->dropColumn(['propinable_id', 'propinable_type']);

            // Restaurar el campo id_consumo (si lo deseas)
            $table->unsignedBigInteger('id_consumo')->nullable()->after('cantidad');
            $table->foreign('id_consumo')->references('id')->on('consumos')
            ->onDelete('cascade')
            ->onUpdate('cascade');
        });
    }
}
