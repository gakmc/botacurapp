<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $fillable = [
        'id_visita',
        'id_producto_entrada',
        'id_producto_fondo',
        'id_producto_acompanamiento',
        'alergias',
        'observacion',
    ];

    // RELACIONES
    public function visita()
    {
        return $this->belongsTo(Visita::class, 'id_visita');
    }

    // Relación con el producto de entrada
    public function productoEntrada()
    {
        return $this->belongsTo(Producto::class, 'id_producto_entrada');
    }

    // Relación con el producto de fondo
    public function productoFondo()
    {
        return $this->belongsTo(Producto::class, 'id_producto_fondo');
    }

    // Relación con el producto de acompañamiento
    public function productoAcompanamiento()
    {
        return $this->belongsTo(Producto::class, 'id_producto_acompanamiento');
    }
}
