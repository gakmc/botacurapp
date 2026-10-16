<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class TipoProducto extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'el tipo de producto';

    protected $auditoriaCampos = [
        'nombre'    => 'Nombre',
        'id_sector' => ['Sector', 'App\Sector@nombre'],
    ];

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
