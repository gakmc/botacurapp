<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtendedFieldsToBotConversacionesTable extends Migration
{
    /**
     * Agrega campos extendidos de recopilación de datos al flujo del bot:
     *   - correo, instagram, genero (perfil cliente)
     *   - celebracion_especial (cumpleaños, despedida, aniversario, etc.)
     *   - tipo_pago (efectivo, transferencia, etc.)
     *   - incluye_masajes (si el cliente quiere agregar masajes extra)
     *   - incluye_menu (si el programa no incluye menú, el cliente puede pedirlo)
     *   - politicas_aceptadas (confirmación de haber leído las políticas)
     *
     * Nuevo flujo de pasos:
     *   0  → Q&A / detección intención
     *   1  → nombre
     *   2  → correo
     *   3  → teléfono
     *   4  → género + instagram (opcional)
     *   5  → selección programa
     *   6  → fecha + disponibilidad
     *   7  → cantidad personas + celebración especial
     *   8  → envío políticas → esperar ACEPTO
     *   9  → datos pago → esperar comprobante → Claude Vision
     *   10 → enviar PDF menú → pedir entrada
     *   11 → guardar entrada → pedir fondo
     *   12 → guardar fondo → pedir acompañamiento → cerrar
     */
    public function up()
    {
        Schema::table('bot_conversaciones', function (Blueprint $table) {
            $table->string('correo')->nullable()->after('nombre_cliente');
            $table->string('instagram')->nullable()->after('correo');
            $table->string('genero')->nullable()->after('instagram');
            $table->string('celebracion_especial')->nullable()->after('fecha_visita');
            $table->string('tipo_pago')->nullable()->after('celebracion_especial');
            $table->tinyInteger('incluye_masajes')->default(0)->after('tipo_pago');
            $table->tinyInteger('incluye_menu')->default(0)->after('incluye_masajes');
            $table->tinyInteger('politicas_aceptadas')->default(0)->after('incluye_menu');
        });
    }

    public function down()
    {
        Schema::table('bot_conversaciones', function (Blueprint $table) {
            $table->dropColumn([
                'correo',
                'instagram',
                'genero',
                'celebracion_especial',
                'tipo_pago',
                'incluye_masajes',
                'incluye_menu',
                'politicas_aceptadas',
            ]);
        });
    }
}
