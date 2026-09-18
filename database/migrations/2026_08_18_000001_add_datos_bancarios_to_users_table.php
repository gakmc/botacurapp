<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega a la tabla users los datos bancarios necesarios para generar el
 * CSV de pago masivo semanal (transferencia a terceros BancoEstado):
 *
 *   banco                   -> nombre del banco (ej: "BancoEstado", "BCI")
 *   tipo_cuenta_bancaria    -> "CTV" (Cuenta Vista), "CCT" (Cuenta Corriente),
 *                              "CAH" (Cuenta de Ahorro), etc.
 *   numero_cuenta_bancaria  -> número de cuenta destino
 *
 * Se cargan manualmente por ahora (sin formulario de autogestión).
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class AddDatosBancariosToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'banco')) {
                $table->string('banco', 60)->nullable()->after('boletea')
                      ->comment('Nombre del banco para transferencia de sueldo');
            }
            if (!Schema::hasColumn('users', 'tipo_cuenta_bancaria')) {
                $table->string('tipo_cuenta_bancaria', 5)->nullable()->after('banco')
                      ->comment('CTV | CCT | CAH');
            }
            if (!Schema::hasColumn('users', 'numero_cuenta_bancaria')) {
                $table->string('numero_cuenta_bancaria', 30)->nullable()->after('tipo_cuenta_bancaria')
                      ->comment('Número de cuenta destino para transferencia de sueldo');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = [];
            foreach (['banco', 'tipo_cuenta_bancaria', 'numero_cuenta_bancaria'] as $c) {
                if (Schema::hasColumn('users', $c)) $cols[] = $c;
            }
            if ($cols) $table->dropColumn($cols);
        });
    }
}
