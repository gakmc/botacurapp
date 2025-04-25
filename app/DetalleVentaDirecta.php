<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleVentaDirecta extends Model
{
    protected $table = 'detalles_ventas_directas';

    protected $fillable = [
        'venta_directa_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function venta()
    {
        return $this->belongsTo(VentaDirecta::class, 'venta_directa_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
