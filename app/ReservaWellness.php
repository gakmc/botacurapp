<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReservaWellness extends Model
{
    protected $table = 'reserva_wellness';

    protected $fillable = [
        'id_reserva',
        'tipo',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }
}
