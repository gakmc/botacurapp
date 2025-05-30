<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RangoSueldoRole extends Model
{
    protected $table = 'rango_sueldo_roles';

    protected $fillable = [
        'role_id',
        'sueldo_base',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
