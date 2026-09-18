<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Egreso extends Model
{
    protected $connection = 'mysql';
    protected $table = 'egresos';

    protected $fillable = [
        'descripcion',
        'total',
        'fecha_egreso',
        'numero_documento',
        'metodo_pago',
        'estado',
        'fuente',
        'observaciones',
    ];

    protected $casts = [
        'total' => 'integer',
        'fecha_egreso' => 'date',
    ];
}
