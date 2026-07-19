<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SiiResumenMensual extends Model
{
    protected $table = 'sii_resumen_mensual';

    protected $fillable = [
        'periodo',
        'compras_neto', 'compras_iva', 'compras_exento', 'compras_total', 'compras_cantidad',
        'ventas_neto',  'ventas_iva',  'ventas_exento',  'ventas_total',  'ventas_cantidad',
        'honorarios_bruto', 'honorarios_retencion', 'honorarios_neto',
        'iva_debito', 'iva_credito', 'iva_diferencia',
        'ultima_sincronizacion',
    ];

    protected $casts = [
        'ultima_sincronizacion' => 'datetime',
    ];

    /** Devuelve año y mes a partir del período YYYYMM */
    public function getAnioAttribute(): int
    {
        return (int) substr($this->periodo, 0, 4);
    }

    public function getMesAttribute(): int
    {
        return (int) substr($this->periodo, 4, 2);
    }
}
