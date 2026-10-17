<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BotMensajePendiente extends Model
{
    protected $table = 'bot_mensajes_pendientes';

    protected $fillable = [
        'telefono', 'nombre', 'phone_number_id', 'tipo', 'mensaje', 'estado', 'respondido_at',
    ];

    protected $dates = ['respondido_at'];

    public function scopePendientes($q)
    {
        return $q->where('estado', 'pendiente');
    }
}
