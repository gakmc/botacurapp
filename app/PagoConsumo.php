<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PagoConsumo extends Model
{
    protected $table = 'pago_consumos';
    protected $fillable = [
        'valor_consumo',
        'pago1',
        'pago2',
        'imagen_pago1',
        'imagen_pago2',
        'id_tipo_transaccion1',
        'id_tipo_transaccion2',
        'id_venta',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function tipoTransaccion1()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }
    
    public function tipoTransaccion2()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }
}
