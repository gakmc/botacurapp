<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PoroPagado extends Model
{
    protected $table = 'poros_pagados';

    protected $fillable = [
        'semana_inicio',
        'semana_fin',
        'fecha_pago',
        'monto'
    ];

    
}
