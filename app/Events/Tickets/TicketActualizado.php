<?php

namespace App\Events\Tickets;

use App\Ticket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Aviso en tiempo real a las bandejas y al detalle del ticket. Se envía al momento
 * (sin cola) y por canales privados de cada departamento involucrado, más el de
 * supervisión; solo lleva identificadores, nunca el contenido del ticket.
 */
class TicketActualizado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public $ticketId;
    public $tipo;
    public $userId;
    protected $roles;

    /**
     * $tipo: creado, comentario, tomar o la acción de Ticket::ACCIONES.
     * $rolesExtra: otros departamentos que deben enterarse (el destino anterior al reenviar).
     */
    public function __construct(Ticket $ticket, $tipo, $userId = null, array $rolesExtra = [])
    {
        $this->ticketId = $ticket->id;
        $this->tipo = $tipo;
        $this->userId = $userId;
        $this->roles = array_unique(array_filter(array_merge(
            [$ticket->rol_origen_id, $ticket->rol_destino_id], $rolesExtra
        )));
    }

    public function broadcastOn()
    {
        $canales = [new PrivateChannel('tickets.supervision')];

        foreach ($this->roles as $rolId) {
            $canales[] = new PrivateChannel('tickets.rol.' . $rolId);
        }

        return $canales;
    }

    public function broadcastAs()
    {
        return 'ticket.actualizado';
    }

    public function broadcastWith()
    {
        return [
            'ticketId' => $this->ticketId,
            'tipo'     => $this->tipo,
            'userId'   => $this->userId,
        ];
    }
}
