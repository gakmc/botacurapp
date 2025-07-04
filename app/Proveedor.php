<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'rut',
        'telefono',
        'correo'
    ];

    public function egresos()
    {
        return $this->hasMany(Egreso::class);
    }
}
