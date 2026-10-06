<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';

    protected $fillable = [
        'cliente', 'solicitante', 'fecha_emision', 'fecha_reserva', 'validez_dias', 'correo', 'observaciones', 'iva_incluido'
    ];

    protected $dates = [
        'fecha_emision', 'fecha_reserva'
    ];

    protected $casts = [
        'iva_incluido' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(CotizacionItem::class, 'cotizacion_id');
    }
}
