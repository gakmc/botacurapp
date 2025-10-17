<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'abono_programa',
        'folio_abono',
        'diferencia_programa',
        'folio_diferencia',
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

    public function giftCards()
    {
        return $this->hasMany(GiftCard::class, 'id_venta');
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


    public function getPendienteDePagoAttribute()
    {
        return $this->total_pagar != 0 && is_null($this->diferencia_programa);
    }

    public function getTieneSaldoAFavorAttribute()
    {
        return $this->total_pagar < 0;
    }

    public function getSaldoAFavorAttribute()
    {
        if ($this->total_pagar < 0) {
            return $this->total_pagar;
        }
        return 0;
    }

    public function getTieneGcAttribute()
    {
        return $this->giftCards()->exists();
    }

}
