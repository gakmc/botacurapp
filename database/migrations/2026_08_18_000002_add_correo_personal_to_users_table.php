<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega correo_personal a la tabla users: el correo donde se le envía
 * al trabajador el comprobante de pago (transferencia bancaria).
 *
 * Distinto de "email" (que es la cuenta de login/sistema) -- puede ser
 * el mismo valor u otro, según lo que informe cada trabajador.
 * Se usa como "Email 1" en el CSV de pago masivo al banco.
 *
 * Compatible Laravel 6 / PHP 7.2
 */
class AddCorreoPersonalToUsersTable extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'correo_personal')) {
                $table->string('correo_personal', 100)->nullable()->after('numero_cuenta_bancaria')
                      ->comment('Correo donde se envía el comprobante de pago (transferencia)');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'correo_personal')) {
                $table->dropColumn('correo_personal');
            }
        });
    }
}
