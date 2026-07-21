<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla bot_conversaciones — sesiones del bot WhatsApp/Instagram.
 * Creada desde schema de producción.
 */
class CreateBotConversacionesTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bot_conversaciones')) {
            return;
        }
        Schema::create('bot_conversaciones', function (Blueprint $table) {
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');
            $table->string('usuario_id');
            $table->enum('canal', ['whatsapp', 'instagram'])->default('whatsapp');
            $table->tinyInteger('paso')->default(0);
            $table->string('nombre_cliente')->nullable();
            // correo, instagram, genero, celebracion_especial, tipo_pago,
            // incluye_masajes, incluye_menu, politicas_aceptadas
            // → los agrega 2026_05_28_200000_add_extended_fields_to_bot_conversaciones_table
            $table->string('telefono')->nullable();
            $table->unsignedInteger('id_programa')->nullable();
            $table->tinyInteger('cantidad_personas')->nullable();
            $table->date('fecha_visita')->nullable();
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_reserva')->nullable();
            $table->tinyInteger('activo')->default(1);
            $table->boolean('requiere_humano')->default(0);
            $table->string('intent_actual', 100)->nullable();
            $table->enum('origen_canal', ['directo', 'canal_difusion', 'instagram', 'publicidad', 'referido'])->default('directo');
            $table->enum('estado', ['en_proceso', 'pago_confirmado', 'abandonado', 'expirado'])->default('en_proceso');
            $table->string('motivo_cierre')->nullable();
            $table->text('ultimo_mensaje')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bot_conversaciones');
    }
}
