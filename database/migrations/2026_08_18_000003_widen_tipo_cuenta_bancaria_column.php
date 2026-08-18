<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Corrige el ancho de tipo_cuenta_bancaria: se definió como varchar(5)
 * pensando en códigos (CTV/CCT/CAH), pero se decidió guardar el texto
 * en español ("Cuenta Corriente", "Cuenta Vista", "Cuenta RUT"), que no
 * entra en 5 caracteres. Se amplía a varchar(30).
 *
 * Usa SQL crudo (no requiere doctrine/dbal) para ser compatible con
 * Laravel 6 / PHP 7.2 tal como está el proyecto.
 */
class WidenTipoCuentaBancariaColumn extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('users', 'tipo_cuenta_bancaria')) {
            DB::statement("ALTER TABLE users MODIFY tipo_cuenta_bancaria VARCHAR(30) NULL COMMENT 'Cuenta Corriente | Cuenta Vista | Cuenta RUT | Cuenta Ahorro'");
        }
    }

    public function down()
    {
        if (Schema::hasColumn('users', 'tipo_cuenta_bancaria')) {
            DB::statement("ALTER TABLE users MODIFY tipo_cuenta_bancaria VARCHAR(5) NULL COMMENT 'CTV | CCT | CAH'");
        }
    }
}
