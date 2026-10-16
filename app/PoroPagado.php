<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PoroPagado extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'semana_inicio' => ['Semana desde', 'fecha'],
        'semana_fin'    => ['Semana hasta', 'fecha'],
        'fecha_pago'    => ['Fecha de pago', 'fecha'],
        'monto'         => ['Monto', 'monto'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $s = app(\App\Services\AuditoriaService::class);

        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' el pago PoroPoro de la semana '
            . $s->fecha($this->semana_inicio) . ' al ' . $s->fecha($this->semana_fin) . ' por ' . $s->monto($this->monto);
    }

    protected $table = 'poros_pagados';

    protected $fillable = [
        'semana_inicio',
        'semana_fin',
        'fecha_pago',
        'monto'
    ];

    
}
