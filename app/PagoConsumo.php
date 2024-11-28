<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PagoConsumo extends Model
{
    protected $table = 'pago_consumos';
    protected $fillable = [
        'valor_consumo',
        'imagen_transaccion',
        'id_consumo',
        'id_tipo_transaccion'
    ];

    public function consumo()
    {
        return $this->belongsTo(Consumo::class, 'id_consumo');
    }

    public function tipoTransaccion()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }
}
