<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoProducto extends Model
{
    protected $table = 'tipos_productos';

    protected $fillable = [
        'nombre', 'id_sector'
    ];


//RELACIONES
public function sector()
{
    return $this->belongsTo(Sector::class , 'id_sector', 'id');
}

public function productos()
{
    return $this->hasMany(Producto::class, 'id_tipo_producto', 'id');
}



//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
}
