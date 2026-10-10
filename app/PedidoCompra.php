<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PedidoCompra extends Model
{
    protected $table = 'pedidos_compra';

    const ESTADOS = [
        'por_comprar'     => 'Por comprar',
        'comprado'        => 'Comprado, por recibir',
        'recibido'        => 'Recibido OK',
        'con_diferencias' => 'Recibido con diferencias',
        'cancelado'       => 'Cancelado',
    ];

    protected $fillable = [
        'id_proveedor', 'estado', 'creado_por', 'comprado_por', 'comprado_at',
        'recibido_por', 'recibido_at', 'egreso_id', 'movimiento_id', 'observacion',
    ];

    protected $casts = ['comprado_at' => 'datetime', 'recibido_at' => 'datetime'];

    public function items()     { return $this->hasMany(PedidoCompraItem::class, 'pedido_id'); }
    public function proveedor() { return $this->belongsTo(Proveedor::class, 'id_proveedor'); }
    public function egreso()    { return $this->belongsTo(Egreso::class, 'egreso_id'); }
    public function creador()   { return $this->belongsTo(User::class, 'creado_por'); }
    public function comprador() { return $this->belongsTo(User::class, 'comprado_por'); }
    public function receptor()  { return $this->belongsTo(User::class, 'recibido_por'); }

    public function nombreProveedor()
    {
        return optional($this->proveedor)->nombre ?: 'Sin proveedor asignado';
    }
}
