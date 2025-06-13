<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'cliente', 'solicitante', 'fecha_emision', 'fecha_reserva', 'validez_dias', 'correo'
    ];

    protected $dates = [
        'fecha_emision', 'fecha_reserva'
    ];



    public function items()
    {
        return $this->hasMany(CotizacionItem::class, 'cotizacion_id');
    }

}
