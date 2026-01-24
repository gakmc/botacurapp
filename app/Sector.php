<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $table = 'sectores';

    protected $fillable = [
        'nombre',
    ];

//RELACIONES
    public function tiposProductos()
    {
        return $this->hasMany('App\TipoProducto');
    }

    public function insumos()
    {
        return $this->hasMany(Insumo::class, 'id_sector');
    }

    public function inventarioMovimientos()
    {
        return $this->hasMany(InventarioMovimiento::class, 'id_sector');
    }



//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
}
