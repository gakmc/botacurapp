<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class UnidadMedida extends Model
{
    protected $table = 'unidades_medidas';

    protected $fillable = [
        'nombre', 'abreviatura', 'tipo', 'descripcion',
    ];

//RELACIONES
    // public function programa()
    // {
    //     return $this->belongsTo('App\Programa');
    // }

// public function reservaciones()
    // {
    //     return $this->belongsToMany('App\Reserva');
    // }

    public function insumos()
    {
        return $this->hasMany(Insumo::class, 'id_unidad_medida');
    }

    public function movimientosDetalle()
    {
        return $this->hasMany(InventarioMovimientoDetalle::class, 'id_unidad_medida');
    }

//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
}
