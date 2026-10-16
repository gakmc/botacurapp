<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class SiiResumenMensual extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'ventas_total'     => ['Ventas', 'monto'],
        'compras_total'    => ['Compras', 'monto'],
        'honorarios_bruto' => ['Honorarios', 'monto'],
        'iva_debito'       => ['IVA débito', 'monto'],
        'iva_credito'      => ['IVA crédito', 'monto'],
        'iva_diferencia'   => ['IVA a pagar', 'monto'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'creó', 'actualizó', 'eliminó') . ' el resumen F29 del período ' . $this->periodo;
    }

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
