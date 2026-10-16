<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PagoEgreso extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'folio'            => 'Folio',
        'monto'            => ['Monto', 'monto'],
        'neto'             => ['Neto', 'monto'],
        'iva'              => ['IVA', 'monto'],
        'impuesto_incluido'=> ['Impuesto incluido', 'bool'],
        'fecha_pago'       => ['Fecha de pago', 'fecha'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' un pago de '
            . app(\App\Services\AuditoriaService::class)->monto($this->monto) . ' del egreso #' . $this->egreso_id;
    }

    protected function auditoriaContexto()
    {
        return $this->egreso;
    }

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
