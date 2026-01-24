<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    protected $table = 'insumos';

    protected $fillable = [
        'nombre',
        'valor',
        'cantidad',
        'stock_critico',
        'id_unidad_medida',
        'id_sector',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'insumo_producto', 'id_producto', 'id_insumo')
            ->withPivot('cantidad_insumo_usar', 'id_unidad_medida', 'total_costo_producto', 'utilidad_producto')
            ->withTimestamps();
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'id_sector');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad_medida', 'id');
    }

    public function unidadMedidaPivot()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad_medida', 'id');
    }

    public function nombreUnidadMedida()
    {
        $unidadMedida = UnidadMedida::find($this->pivot->id_unidad_medida);
        return $unidadMedida ? $unidadMedida->nombre : 'No disponible';
    }

    public function movimientosDetalle()
    {
        return $this->hasMany(InventarioMovimientoDetalle::class, 'id_insumo');
    }

}
