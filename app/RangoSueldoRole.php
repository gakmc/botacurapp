<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class RangoSueldoRole extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'role_id'       => ['Rol', 'App\Role@name'],
        'sueldo_base'   => ['Sueldo base', 'monto'],
        'vigente_desde' => ['Vigente desde', 'fecha'],
        'vigente_hasta' => ['Vigente hasta', 'fecha'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' el sueldo base del rol ' . optional($this->role)->name;
    }

    protected $table = 'rango_sueldo_roles';

    protected $fillable = [
        'role_id',
        'sueldo_base',
        'vigente_desde',
        'vigente_hasta',
    ];

    protected $casts = [
        'vigente_desde' => 'date',
        'vigente_hasta' => 'date',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
