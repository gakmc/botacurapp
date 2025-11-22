<?php

namespace App\Events\Menu;

use App\Reserva;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MenuEntregadoEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // public $reserva;
    public $idReserva;

    public function __construct($idReserva)
    {
        // $this->reserva = $reserva->load('cliente','programa','menus','menus.productoEntrada', 'menus.productoFondo', 'menus.productoAcompanamiento');

        $this->idReserva = $idReserva;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('entregar-menu');
    }

    public function broadcastAs(){
        return 'menuEntregado';
    }
}
