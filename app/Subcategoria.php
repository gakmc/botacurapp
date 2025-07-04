<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    protected $table = 'subcategorias_compras';

    protected $fillable = [
        'nombre',
        'categoria_id'
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaCompra::class, 'categoria_id');
    }

    public function egresos()
    {
        return $this->hasMany(Egreso::class);
    }
}
