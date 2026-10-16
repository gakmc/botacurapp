<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class UserImpuesto extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'retiene_impuestos' => ['Retiene impuestos', 'bool'],
        'retencion_desde'   => ['Retención desde', 'fecha'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'configuró', 'cambió', 'eliminó') . ' la retención de impuestos de ' . optional($this->user)->name;
    }

    protected function auditoriaContexto()
    {
        return $this->user;
    }

    protected $table = 'user_impuesto';

    protected $fillable = ['user_id', 'retiene_impuestos', 'retencion_desde'];

    protected $casts = ['retiene_impuestos' => 'boolean', 'retencion_desde' => 'datetime'];

    public function user() {
        return $this->belongsTo(User::class);
    }

}
