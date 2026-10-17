<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBotMensajesPendientesTable extends Migration
{
    public function up()
    {
        Schema::create('bot_mensajes_pendientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('telefono', 30)->index();
            $table->string('nombre')->nullable();
            $table->string('phone_number_id', 40)->nullable();
            $table->string('tipo', 20)->default('text');
            $table->text('mensaje');
            $table->string('estado', 20)->default('pendiente')->index(); // pendiente | respondido | atendido
            $table->timestamp('respondido_at')->nullable();
            $table->timestamps();
        });

        // Ajustes del bot (categoría "sistema", no aparecen en las pestañas de textos)
        $ajustes = [
            ['clave' => 'bot_activo',      'etiqueta' => 'Bot de WhatsApp activo',          'contenido' => '1'],
            ['clave' => 'alerta_correo',   'etiqueta' => 'Correo para alertas del bot',     'contenido' => 'hola@botacura.cl'],
            ['clave' => 'alerta_telefono', 'etiqueta' => 'WhatsApp para alertas del bot',   'contenido' => ''],
        ];
        foreach ($ajustes as $i => $a) {
            if (!DB::table('bot_secciones_texto')->where('clave', $a['clave'])->exists()) {
                DB::table('bot_secciones_texto')->insert($a + [
                    'categoria'  => 'sistema',
                    'orden'      => 900 + $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('bot_mensajes_pendientes');
        DB::table('bot_secciones_texto')->whereIn('clave', ['bot_activo', 'alerta_correo', 'alerta_telefono'])->delete();
    }
}
