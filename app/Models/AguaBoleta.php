<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Auditoria;
use Illuminate\Database\Eloquent\Model;

class AguaBoleta extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'numero_boleta'     => 'N° boleta',
        'periodo'           => 'Período',
        'consumo_m3'        => 'Consumo (m³)',
        'total_a_pagar'     => ['Total a pagar', 'monto'],
        'fecha_limite_pago' => ['Fecha límite', 'fecha'],
        'origen'            => 'Origen',
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' la boleta de agua N° ' . $this->numero_boleta
            . ' (' . $this->periodo . ') por ' . app(\App\Services\AuditoriaService::class)->monto($this->total_a_pagar);
    }

    protected $connection = 'mysql';
    protected $table = 'agua_boletas';

    protected $fillable = [
        'egreso_id',
        'numero_boleta',
        'periodo',
        'fecha_emision',
        'consumo_m3',
        'monto_consumo',
        'cargo_fijo',
        'ajuste_mes_anterior',
        'intereses_atraso',
        'ajuste_mes_actual',
        'total_mes',
        'saldo_anterior',
        'total_a_pagar',
        'fecha_limite_pago',
        'documento',
        'observacion',
        'origen',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_limite_pago' => 'date',
    ];

    public function egreso()
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }
}
