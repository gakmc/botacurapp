<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBotConversacionesTable extends Migration
{
    /**
     * Tabla para gestionar el estado de conversaciones multi-turno
     * del chatbot de WhatsApp e Instagram.
     *
     * Flujo de pasos:
     *   0 → saludo / detección de intención
     *   1 → usuario eligió programa
     *   2 → usuario indicó cantidad de personas
     *   3 → usuario indicó fecha de visita
     *   4 → confirmación y creación de reserva
     *   5 → esperando comprobante de pago
     */
    public function up()
    {
        Schema::create('bot_conversaciones', function (Blueprint $table) {
            $table->charset   = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';
            $table->increments('id');

            // Identificador del usuario en el canal (número WA o user ID de IG)
            $table->string('usuario_id')->index();

            // Canal de origen
            $table->enum('canal', ['whatsapp', 'instagram'])->default('whatsapp');

            // Paso actual del flujo de reserva (0-5)
            $table->tinyInteger('paso')->default(0);

            // Datos recopilados durante la conversación
            $table->string('nombre_cliente')->nullable();
            $table->string('telefono')->nullable();
            $table->unsignedInteger('id_programa')->nullable();
            $table->tinyInteger('cantidad_personas')->nullable();
            $table->date('fecha_visita')->nullable();

            // Referencias a registros creados
            $table->unsignedInteger('id_cliente')->nullable();
            $table->unsignedInteger('id_reserva')->nullable();

            // Control de estado
            // activo = 1: conversación en curso
            // activo = 0: conversación finalizada (reserva creada o abandonada)
            $table->tinyInteger('activo')->default(1)->index();

            // Motivo de cierre para análisis
            // valores: 'reserva_creada', 'pago_registrado', 'abandonada', 'derivada_humano'
            $table->string('motivo_cierre')->nullable();

            // Último mensaje recibido (útil para debugging y contexto)
            $table->text('ultimo_mensaje')->nullable();

            $table->timestamps();

            // Índice compuesto para buscar rápido la conversación activa de un usuario
            $table->index(['usuario_id', 'activo']);

            // Claves foráneas opcionales (nullable para no bloquear si aún no existe el registro)
            $table->foreign('id_programa')
                ->references('id')->on('programas')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('id_cliente')
                ->references('id')->on('clientes')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('id_reserva')
                ->references('id')->on('reservas')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bot_conversaciones');
    }
}
