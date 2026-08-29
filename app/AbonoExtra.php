<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AbonoExtra extends Model
{
    protected $table = 'abonos_extra';

    protected $fillable = [
        'id_venta',
        'monto',
        'fecha_abono',
        'id_tipo_transaccion',
        'folio',
        'user_id',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function tipoTransaccion()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
