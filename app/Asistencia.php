<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use Auditable;

    // Financiera: la asistencia genera y elimina los sueldos del día
    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'fecha' => ['Fecha', 'fecha'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' el registro de asistencia del '
            . app(\App\Services\AuditoriaService::class)->fecha($this->getAttributes()['fecha'] ?? null);
    }

    /** Asistentes, para AuditoriaService::sincronizar() */
    public function resumenUsuariosAuditoria()
    {
        return $this->users()->orderBy('name')->pluck('name')->implode(', ');
    }

    protected $table = 'asistencias';

    protected $fillable = [
        "user_id",
        "fecha",
        "observacion"
    ];

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
