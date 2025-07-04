<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoriaCompra extends Model
{
    protected $table = 'categorias_compras';

    protected $fillable = [
        'nombre'
    ];


    //RELACIONES
    public function subcategorias()
    {
        return $this->hasMany(Subcategoria::class, 'categoria_id');
    }

    public function egresos()
    {
        return $this->hasMany(Egreso::class, 'categoria_id');
    }


    //ALMACENAMIENTO

    //VALIDACION

    //RECUPERACION DE INFORMACION

    //OTRAS OPERACIONES
}
