<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separa "guardar bono/motivo" de "confirmar que se hizo la transferencia".
 *
 * Antes, un registro en sueldos_pagados = pago ya hecho, sin distinción.
 * Ahora se puede guardar bono/motivo de un usuario en cualquier momento
 * (para que quede sumado en el Total antes de exportar el CSV), y recién
 * al marcar "confirmado" se considera que la plata efectivamente se
 * transfirió (después de subir el CSV al banco).
 */
class AddConfirmadoToSueldosPagadosTable extends Migration
{
    public function up()
    {
        Schema::table('sueldos_pagados', function (Blueprint $table) {
            if (!Schema::hasColumn('sueldos_pagados', 'confirmado')) {
                $table->boolean('confirmado')->default(false)->after('motivo')
                      ->comment('true = se confirmó que la transferencia bancaria ya se hizo');
            }
            if (!Schema::hasColumn('sueldos_pagados', 'confirmado_at')) {
                $table->timestamp('confirmado_at')->nullable()->after('confirmado');
            }
        });
    }

    public function down()
    {
        Schema::table('sueldos_pagados', function (Blueprint $table) {
            $cols = [];
            foreach (['confirmado', 'confirmado_at'] as $c) {
                if (Schema::hasColumn('sueldos_pagados', $c)) $cols[] = $c;
            }
            if ($cols) $table->dropColumn($cols);
        });
    }
}
