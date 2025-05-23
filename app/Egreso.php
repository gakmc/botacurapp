<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Egreso extends Model
{
    protected $table = 'egresos';

    protected $fillable = [
        'categoria_id',
        'fecha',
        'monto',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaCompra::class, 'categoria_id');
    }

    public function getFechaAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
}
