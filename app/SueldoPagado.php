<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class SueldoPagado extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'user_id'      => ['Trabajador', 'App\User@name'],
        'semana_inicio'=> ['Semana desde', 'fecha'],
        'semana_fin'   => ['Semana hasta', 'fecha'],
        'fecha_pago'   => ['Fecha de pago', 'fecha'],
        'monto'        => ['Monto', 'monto'],
        'bono'         => ['Bono', 'monto'],
        'motivo'       => 'Motivo',
        'confirmado'   => ['Confirmado', 'bool'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $s = app(\App\Services\AuditoriaService::class);

        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' el pago de sueldo de ' . optional($this->user)->name
            . ' (semana ' . $s->fecha($this->semana_inicio) . ' al ' . $s->fecha($this->semana_fin) . ') por ' . $s->monto($this->monto);
    }

    protected function auditoriaContexto()
    {
        return $this->user;
    }


    protected $table = 'sueldos_pagados';

    protected $fillable = [
        'user_id',
        'semana_inicio',
        'semana_fin',
        'fecha_pago',
        'monto',
        'bono',
        'motivo',
        'confirmado',
        'confirmado_at',
    ];

    protected $casts = [
        'confirmado' => 'boolean',
        'confirmado_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
