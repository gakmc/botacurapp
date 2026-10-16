<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Propina extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected function auditoriaContexto()
    {
        return $this->propinable instanceof Consumo ? optional($this->propinable->venta)->reserva : null;
    }

    protected $auditoriaCampos = [
        'cantidad' => ['Monto', 'monto'],
        'fecha'    => ['Fecha', 'fecha'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $consumo = $this->propinable instanceof Consumo ? $this->propinable : null;
        $reserva = $this->textoReservaAuditoria(optional(optional($consumo)->venta)->reserva);
        $monto   = app(\App\Services\AuditoriaService::class)->monto($this->cantidad);

        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' la propina de ' . $monto . ' de ' . $reserva;
    }

    protected $table = 'propinas';

    protected $fillable = [
        'fecha', 'cantidad', 'id_consumo'
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'propina_user', 'id_propina', 'id_user')
                    ->withPivot('monto_asignado')
                    ->withTimestamps();
    }

    public function consumo()
    {
        return $this->belongsTo(Consumo::class, 'id_consumo');
    }

    public function propinable()
    {
        return $this->morphTo();
    }
}
