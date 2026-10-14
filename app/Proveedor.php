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
        'correo',
        'subcategoria_id'
    ];

    public function egresos()
    {
        return $this->hasMany(Egreso::class);
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }
}
