<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $table = 'sectores';

    protected $fillable = [
        'nombre'
    ];


//RELACIONES
public function tiposProductos()
{
    return $this->hasMany('App\TipoProducto');
}

public function insumos()
{
    return $this->hasMany(Insumo::class, 'id_sector');
}

// public function reservaciones()
// {
//     return $this->belongsToMany('App\Reserva');
// }

//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
}
