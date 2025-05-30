<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AnularSueldoUsuario extends Model
{
    protected $table = 'anular_sueldo_usuarios';

    protected $fillable = [
        'user_id',
        'salario',
        'motivo',
        'creado_por',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
