<?php

namespace App\Events\Consumos;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EstadoConsumoActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    public $detalleId;
    public $estado;
    public $producto;
    public function __construct($detalleId, $estado, $producto)
    {
        $this->detalleId = $detalleId;
        $this->estado = $estado;
        $this->producto = $producto;
    }

    public function broadcastOn()
    {
        return new Channel('consumo-canal-actualizar');
    }

    public function broadcastWidth() {
        return[
            'detalleId' => $this->detalleId,
            'estado' => $this->estado,
            'producto' => $this->producto,
        ];
    }
}
