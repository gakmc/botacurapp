<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoCompraItem extends Model
{
    protected $table = 'pedido_compra_items';

    const ESTADOS = [
        'pendiente' => 'Pendiente',
        'comprado'  => 'Comprado',
        'no_habia'  => 'No había',
        'ok'        => 'Llegó OK',
        'faltante'  => 'Llegó menos',
        'sobrante'  => 'Llegó más',
        'no_llego'  => 'No llegó',
        'extra'     => 'Llegó sin pedirse',
    ];

    protected $fillable = [
        'pedido_id', 'id_insumo', 'cantidad_pedida', 'cantidad_comprada', 'costo_total_neto',
        'cantidad_recibida', 'estado', 'nota',
    ];

    public function pedido() { return $this->belongsTo(PedidoCompra::class, 'pedido_id'); }
    public function insumo() { return $this->belongsTo(Insumo::class, 'id_insumo'); }
}
