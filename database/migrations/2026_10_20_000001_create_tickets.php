<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tickets (tareas pendientes) entre departamentos: cada rol con recibe_tickets
 * puede enviar a otro rol. Los estados y transiciones viven en App\Ticket.
 */
class CreateTickets extends Migration
{
    /** Roles habilitados al crear el módulo (los definidos en el .env). */
    const ROLES_INICIALES = [
        'admin_role', 'administracion_role', 'anfitriona_role', 'aseo_role', 'barman_role', 'cocina_role',
        'garzon_role', 'informatica_role', 'jefe_local_role', 'mantencion_role', 'masoterapeuta_role',
    ];

    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('recibe_tickets')->default(false)->after('description');
        });

        $slugs = array_map(function ($clave) {
            return config('app.' . $clave);
        }, self::ROLES_INICIALES);
        DB::table('roles')->whereIn('slug', $slugs)->update(['recibe_tickets' => true]);

        Schema::create('tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('titulo', 150);
            $table->text('descripcion')->nullable();
            $table->string('prioridad', 10)->default('normal');   // baja | normal | alta | urgente
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedInteger('rol_origen_id');
            $table->unsignedInteger('rol_destino_id');
            $table->unsignedInteger('creado_por')->nullable();
            $table->unsignedInteger('responsable_id')->nullable(); // quien lo tomó en el destino
            $table->string('ubicacion', 150)->nullable();          // texto libre (con sugerencias al escribir)

            // Fechas por etapa (tiempos de respuesta) y último cambio de estado (autocierre)
            $table->timestamp('recepcionado_at')->nullable();
            $table->timestamp('solucionado_at')->nullable();
            $table->timestamp('verificacion_at')->nullable();
            $table->timestamp('finalizado_at')->nullable();
            $table->timestamp('anulado_at')->nullable();
            $table->timestamp('estado_cambiado_at')->nullable();
            $table->boolean('finalizado_automatico')->default(false);
            $table->timestamp('recordatorio_enviado_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('rol_origen_id')->references('id')->on('roles')->onUpdate('cascade');
            $table->foreign('rol_destino_id')->references('id')->on('roles')->onUpdate('cascade');
            $table->foreign('creado_por')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('responsable_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');

            $table->index(['rol_destino_id', 'estado']);
            $table->index(['rol_origen_id', 'estado']);
            $table->index(['estado', 'estado_cambiado_at']);
            $table->index('responsable_id');
        });

        Schema::create('ticket_imagenes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->string('ruta');                              // storage/app/tickets/Y/m
            $table->string('etapa', 10)->default('reporte');     // reporte | solucion
            $table->unsignedInteger('subida_por')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('subida_por')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
        });

        // Línea de tiempo visible en el ticket (la auditoría es aparte)
        Schema::create('ticket_eventos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('ticket_id');
            $table->unsignedInteger('user_id')->nullable();     // null = sistema (autocierre)
            $table->string('tipo', 20);                         // estado | comentario | reenvio | toma
            $table->unsignedInteger('rol_id')->nullable();      // reenvio: departamento al que se reenvió
            $table->string('estado_anterior', 20)->nullable();
            $table->string('estado_nuevo', 20)->nullable();
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
            $table->foreign('rol_id')->references('id')->on('roles')->onUpdate('cascade')->onDelete('set null');
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ticket_eventos');
        Schema::dropIfExists('ticket_imagenes');
        Schema::dropIfExists('tickets');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('recibe_tickets');
        });
    }
}
