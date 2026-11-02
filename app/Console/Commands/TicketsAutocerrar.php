<?php

namespace App\Console\Commands;

use App\Auditoria;
use App\Services\AuditoriaService;
use App\Services\TicketNotificacionService;
use App\Services\TicketService;
use App\Ticket;
use Illuminate\Console\Command;
use Throwable;

/**
 * Tickets Solucionados / En verificación que el origen no confirma:
 *  - un día antes del plazo, recordatorio push al origen (una vez por cada vez que llega a ese estado);
 *  - al cumplirse config('app.tickets_dias_autocierre') días sin movimiento, se finalizan solos.
 *
 * No usa ComandoAuditable (que deja un solo resumen): cada cierre queda en la auditoría
 * de su propio ticket, a nombre del Sistema. Si no hay nada que hacer no registra nada.
 */
class TicketsAutocerrar extends Command
{
    protected $signature = 'tickets:autocerrar {--programado : Uso interno del scheduler}';
    protected $description = 'Recuerda y finaliza automáticamente los tickets solucionados que el origen no confirmó.';

    public function handle(TicketService $tickets, TicketNotificacionService $notificaciones)
    {
        $dias = (int) config('app.tickets_dias_autocierre');
        if ($dias < 1) {
            $this->error('TICKETS_DIAS_AUTOCIERRE debe ser al menos 1.');
            return 1;
        }

        app(AuditoriaService::class)->establecerActor(null, Auditoria::ACTOR_SISTEMA,
            $this->getName() . ($this->option('programado') ? '' : ' (manual)'));

        $cerrados = 0;
        foreach (Ticket::sinConfirmarDesde($dias)->get() as $ticket) {
            try {
                $tickets->ejecutarAccion($ticket, 'autocerrar');
                $cerrados++;
            } catch (Throwable $e) {
                // Si alguien lo movió justo ahora, ya no corresponde cerrarlo
                $this->error($ticket->codigo . ': ' . $e->getMessage());
            }
        }

        // Recordatorio un día antes (si el plazo es de 1 día, no hay "día antes")
        $recordados = 0;
        if ($dias > 1) {
            $porVencer = Ticket::sinConfirmarDesde($dias - 1)
                ->where(function ($q) {
                    $q->whereNull('recordatorio_enviado_at')->orWhereColumn('recordatorio_enviado_at', '<', 'estado_cambiado_at');
                })
                ->with(['creador', 'rolOrigen', 'rolDestino'])
                ->get();

            foreach ($porVencer as $ticket) {
                $notificaciones->recordatorio($ticket);
                // Query builder: marca técnica, no es un cambio del ticket para la auditoría
                Ticket::whereKey($ticket->id)->update(['recordatorio_enviado_at' => now()]);
                $recordados++;
            }
        }

        if ($cerrados || $recordados) {
            $this->info("Tickets finalizados automáticamente: {$cerrados}. Recordatorios enviados: {$recordados}.");
        }

        return 0;
    }
}
