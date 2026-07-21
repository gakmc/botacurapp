<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega tipo_servicio a la tabla menus.
 *
 * Valores:
 *   'desayuno'  — el cliente eligió desayuno (10:30 – 12:00)
 *   'once'      — el cliente eligió once (17:00 – 18:15)
 *   NULL        — sin asignar / legacy o programa sin desayuno/once
 *
 * NOTA: 'almuerzo' NO va aquí. El almuerzo es el servicio estándar de todos los
 * programas y se registra a través de la selección de menú (entrada, fondo, acompañamiento).
 * Este campo solo aplica para los programas que incluyen desayuno y/o once como servicio extra.
 *
 * Compatible con reservas creadas por bot WhatsApp y por backoffice.
 */
class AddTipoServicioToMenus extends Migration
{
    public function up()
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->string('tipo_servicio', 20)->nullable()->after('id_reserva');
        });
    }

    public function down()
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('tipo_servicio');
        });
    }
}
