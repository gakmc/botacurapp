<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ReservaDesayunoOnce extends Model
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
        return $this->segunEvento($evento, 'agregó', 'editó', 'quitó') . ' ' . $this->tipo . ' '
            . ($evento === Auditoria::EVENTO_ELIMINO ? 'de ' : 'a ') . $this->textoReservaAuditoria($this->reserva);
    }

    protected $table = 'reserva_desayuno_once';

    protected $fillable = [
        'id_reserva',
        'tipo',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }
}
