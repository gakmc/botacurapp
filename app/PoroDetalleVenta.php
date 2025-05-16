<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PoroDetalleVenta extends Model
{
    protected $table = 'poro_detalle_ventas';

    protected $fillable = [
        'poro_venta_id',
        'poro_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    // Cada detalle pertenece a una venta
    public function venta()
    {
        return $this->belongsTo(PoroPoroVenta::class, 'poro_venta_id');
    }

    // Cada detalle se refiere a un poro
    public function poro()
    {
        return $this->belongsTo(PoroPoro::class, 'poro_id');
    }
}
