<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventarioMovimientoDetalle extends Model
{
    protected $table = 'inventario_movimiento_detalles';

    protected $fillable = [
        'movimiento_id',
        'id_insumo',
        'cantidad',
        'id_unidad_medida',
        'cantidad_base',
        'costo_unitario',
        'costo_total'
    ];

    public function movimiento()
    {
        return $this->belongsTo(InventarioMovimiento::class, 'movimiento_id');    
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class, 'id_insumo');    
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'id_unidad_medida');
    }
}
