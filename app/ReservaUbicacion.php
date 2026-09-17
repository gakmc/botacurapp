<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReservaUbicacion extends Model
{
    protected $table = 'reserva_ubicaciones';

    protected $fillable = [
        'id_reserva',
        'id_ubicacion',
        'cantidad_personas',
        'rol',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion');
    }
}
