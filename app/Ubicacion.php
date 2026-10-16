<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'la ubicación';

    protected $auditoriaCampos = [
        'nombre'        => 'Nombre',
        'espacio_tipo'  => 'Tipo de espacio',
        'sub_tipo'      => 'Subtipo',
        'capacidad_min' => 'Capacidad mínima',
        'capacidad_max' => 'Capacidad máxima',
        'tiene_terraza' => ['Tiene terraza', 'bool'],
        'activo'        => ['Activa', 'bool'],
    ];


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
