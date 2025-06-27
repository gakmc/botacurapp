<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SueldoPagado extends Model
{

    protected $table = 'sueldos_pagados';

    protected $fillable = [
        'user_id',
        'semana_inicio',
        'semana_fin',
        'fecha_pago',
        'monto'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
