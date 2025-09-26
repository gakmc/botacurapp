<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PagoEgreso extends Model
{
    protected $table = 'pagos_egresos';

    protected $fillable = [
        'egreso_id',
        'folio',
        'monto',
        'neto',
        'iva',
        'impuesto_incluido',
        'fecha_pago'
    ];

    protected $dates = ['fecha_pago'];

    public function egreso()
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }
}
