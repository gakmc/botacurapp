<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DetalleServiciosExtra extends Model
{
    protected $table = 'detalle_servicios_extra';

    protected $fillable = [
        'cantidad_servicio',
        'subtotal',
        'id_consumo',
        'id_servicio_extra'
    ];

    public function consumo()
    {
        return $this->belongsTo(Consumo::class, 'id_consumo');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'id_servicio_extra');
    }
}
