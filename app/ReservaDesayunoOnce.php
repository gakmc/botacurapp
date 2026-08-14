<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReservaDesayunoOnce extends Model
{
    protected $table = 'reserva_desayuno_once';

    protected $fillable = [
        'id_reserva',
        'tipo',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }
}
