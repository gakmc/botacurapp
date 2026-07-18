<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega historial_json a bot_conversaciones para que el BotController
 * pueda enviar el historial completo de mensajes a Claude como contexto.
 *
 * Estructura del JSON: [ { "role": "user"|"assistant", "content": "..." }, ... ]
 */
class AddHistorialJsonToBotConversaciones extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bot_conversaciones')) {
            return;
        }
        if (!Schema::hasColumn('bot_conversaciones', 'historial_json')) {
            Schema::table('bot_conversaciones', function (Blueprint $table) {
                $table->text('historial_json')->nullable()->after('ultimo_mensaje');
            });
        }
    }

    public function down()
    {
        Schema::table('bot_conversaciones', function (Blueprint $table) {
            $table->dropColumn('historial_json');
        });
    }
}
