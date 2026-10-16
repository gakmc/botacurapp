<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Asignacion extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'fecha' => ['Fecha', 'fecha'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' la asignación de turno del '
            . app(\App\Services\AuditoriaService::class)->fecha($this->getAttributes()['fecha'] ?? null);
    }

    /** Personal asignado, para AuditoriaService::sincronizar() */
    public function resumenUsuariosAuditoria()
    {
        return $this->users()->orderBy('name')->pluck('name')->implode(', ');
    }

    protected $table = 'asignaciones';
    
    protected $fillable = [
        'fecha'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'asignacion_user', 'asignacion_id', 'user_id')->withTimestamps();
    }
}
