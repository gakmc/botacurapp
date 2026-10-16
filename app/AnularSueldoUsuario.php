<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class AnularSueldoUsuario extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'user_id' => ['Trabajador', 'App\User@name'],
        'salario' => ['Sueldo especial', 'monto'],
        'motivo'  => 'Motivo',
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'fijó un sueldo especial para', 'editó el sueldo especial de', 'quitó el sueldo especial de')
            . ' ' . optional($this->user)->name;
    }

    protected function auditoriaContexto()
    {
        return $this->user;
    }

    protected $table = 'anular_sueldo_usuarios';

    protected $fillable = [
        'user_id',
        'salario',
        'motivo',
        'creado_por',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
