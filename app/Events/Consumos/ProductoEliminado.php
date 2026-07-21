<?php

namespace App\Events\Consumos;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductoEliminado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $pedidoKey;
    public $idDetalle;

    public function __construct($pedidoKey, $idDetalle)
    {
        $this->pedidoKey = $pedidoKey;
        $this->idDetalle = $idDetalle;
    }

    public function broadcastOn()
    {
        return new Channel('consumo-canal-actualizar');
    }

    public function broadcastWith()
    {
        return [
            'pedido_key' => $this->pedidoKey,
            'id_detalle' => $this->idDetalle,
        ];
    }
}
