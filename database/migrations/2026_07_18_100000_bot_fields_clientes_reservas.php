<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes necesarios para que el bot WhatsApp pueda crear clientes y reservas:
 *
 * 1. clientes.sexo → nullable (el bot no pregunta sexo)
 * 2. reservas.fuente → nueva columna para identificar origen (bot_whatsapp, backoffice, woocommerce...)
 * 3. reservas.estado → ya existe en producción, solo verificar
 */
class BotFieldsClientesReservas extends Migration
{
    public function up()
    {
        // 1. Hacer nullable sexo en clientes
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('sexo')->nullable()->change();
        });

        // 2. Agregar fuente a reservas (si no existe)
        if (!Schema::hasColumn('reservas', 'fuente')) {
            Schema::table('reservas', function (Blueprint $table) {
                $table->string('fuente')->nullable()->default('backoffice')->after('estado');
                // Valores posibles: backoffice | bot_whatsapp | woocommerce
            });
        }
    }

    public function down()
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('sexo')->nullable(false)->change();
        });

        if (Schema::hasColumn('reservas', 'fuente')) {
            Schema::table('reservas', function (Blueprint $table) {
                $table->dropColumn('fuente');
            });
        }
    }
}
