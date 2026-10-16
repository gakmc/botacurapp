<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ReservaWellness extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected function auditoriaContexto()
    {
        return $this->reserva;
    }

    protected $auditoriaCampos = [
        'tipo' => 'Tipo',
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'asignó', 'editó', 'quitó') . ' ' . $this->tipo . ' (wellness) '
            . ($evento === Auditoria::EVENTO_ELIMINO ? 'de ' : 'a ') . $this->textoReservaAuditoria($this->reserva);
    }

    protected $table = 'reserva_wellness';

    protected $fillable = [
        'id_reserva',
        'tipo',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }
}
