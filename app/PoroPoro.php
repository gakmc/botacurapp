<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PoroPoro extends Model
{
    protected $table = 'poro_poros';

    protected $fillable = [
        'nombre',
        'valor',
        'descripcion',
    ];

    // Un poro puede estar en muchos detalles de venta
    public function detalleVentas()
    {
        return $this->hasMany(PoroDetalleVenta::class, 'poro_id');
    }
}
