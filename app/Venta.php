<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'abono_programa',
        'imagen_abono',
        'diferencia_programa',
        'imagen_diferencia',
        'descuento',
        'total_pagar',
        'id_reserva',
        'id_tipo_transaccion_abono',
        'id_tipo_transaccion_diferencia',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva')->withDefault();
    }

    public function tipoTransaccionAbono()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion_abono');
    }

    public function tipoTransaccionDiferencia()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion_diferencia');
    }

    // public function consumos()
    // {
    //     return $this->hasMany(Consumo::class, 'id_venta');
    // }

    public function consumo()
    {
        return $this->hasOne(Consumo::class, 'id_venta');
    }

    public function pagoConsumo()
    {
        return $this->hasOne(PagoConsumo::class, 'id_venta');
    }
}
