<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class FacturaFoto extends Model
{
    protected $table = 'facturas_fotos';

    protected $fillable = [
        'archivos', 'extraccion', 'verificacion', 'egreso_id', 'pedido_id',
        'estado', 'error', 'subida_por', 'confirmada_por',
    ];

    protected $casts = [
        'archivos'     => 'array',
        'extraccion'   => 'array',
        'verificacion' => 'array',
    ];

    public function egreso()  { return $this->belongsTo(Egreso::class, 'egreso_id'); }
    public function pedido()  { return $this->belongsTo(PedidoCompra::class, 'pedido_id'); }
    public function usuario() { return $this->belongsTo(User::class, 'subida_por'); }
}
