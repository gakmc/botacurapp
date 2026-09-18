<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AguaBoleta extends Model
{
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
