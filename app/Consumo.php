<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Consumo extends Model
{
    protected $fillable = [
        'subtotal',
        'total_consumo',
        'id_venta',
    ];

    // public function venta()
    // {
    //     return $this->belongsTo(Venta::class, 'id_venta');
    // }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function detallesConsumos()
    {
        return $this->hasMany(DetalleConsumo::class, 'id_consumo');
    }

    public function detalleServiciosExtra()
    {
        return $this->hasMany(DetalleServiciosExtra::class, 'id_consumo');
    }

    public function pagosConsumos()
    {
        return $this->hasMany(PagoConsumo::class, 'id_consumo');
    }

    public function propina()
    {
        return $this->morphOne(Propina::class, 'propinable');
    }


    //Validacion
    public function getDetallesConsumoAttribute()
    {
        
        return $this->detallesConsumos();
    }
}
