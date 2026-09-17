<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{

    protected $table = 'ubicaciones';

    protected $fillable = [
        'nombre',
        'espacio_tipo',
        'sub_tipo',
        'capacidad_min',
        'capacidad_max',
        'tiene_terraza',
        'activo',
    ];

    public function visitas(){
        return $this->hasMany(Visita::class, 'id_ubicacion');
    }

    public function reservasAsignadas()
    {
        return $this->hasMany(ReservaUbicacion::class, 'id_ubicacion');
    }


//RELACIONES
// public function programa()
// {
//     return $this->belongsTo('App\Programa');
// }

// public function reservaciones()
// {
//     return $this->belongsToMany('App\Reserva');
// }

//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
}
