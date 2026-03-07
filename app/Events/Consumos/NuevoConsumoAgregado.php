<?php

namespace App\Events\Consumos;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NuevoConsumoAgregado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $mensaje;
    public function __construct($mensaje)
    {
        $this->mensaje = $mensaje;
    }

    public function broadcastOn()
    {
        return new Channel('consumo-canal');
    }

    public function broadcastWith()
    {
        return [
            'mensaje'    => $this->mensaje['mensaje'],
            'pedido_id'  => $this->mensaje['pedido_id'],
            'cliente'    => $this->mensaje['cliente'],
            'ubicacion'  => $this->mensaje['ubicacion'],
            'productos'  => $this->mensaje['productos'],
            'estado'     => $this->mensaje['estado'],
        ];
    }
}
