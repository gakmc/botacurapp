<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleConsumo extends Model
{
    protected $table = 'detalles_consumos';
    
    protected $fillable = [
        'cantidad_producto',
        'subtotal',
        'genera_propina',
        'id_consumo',
        'id_producto',
        'estado'
    ];
    

    public function consumo()
    {
        return $this->belongsTo(Consumo::class, 'id_consumo');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    
}
