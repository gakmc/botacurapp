<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CotizacionItem extends Model
{
    protected $table = 'cotizacion_items';

    protected $fillable = [
        'cotizacion_id', 'itemable_id', 'itemable_type', 'cantidad', 'valor_neto', 'total'
    ];


    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class,'cotizacion_id');
    }

    public function itemable()
    {
        return $this->morphTo();
    }

}
