<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class AbonoExtra extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected function auditoriaContexto()
    {
        return optional($this->venta)->reserva;
    }

    protected $auditoriaCampos = [
        'monto'               => ['Monto', 'monto'],
        'fecha_abono'         => ['Fecha abono', 'fecha'],
        'id_tipo_transaccion' => ['Medio de pago', 'App\TipoTransaccion@nombre'],
        'folio'               => 'Folio',
    ];

    protected function auditoriaDescripcion($evento)
    {
        $monto   = app(\App\Services\AuditoriaService::class)->monto($this->monto);
        $reserva = $this->textoReservaAuditoria(optional($this->venta)->reserva);

        if ($evento === Auditoria::EVENTO_CREO) {
            return 'registró un abono extra de ' . $monto . ' en la venta de ' . $reserva;
        }
        if ($evento === Auditoria::EVENTO_ELIMINO) {
            return 'eliminó el abono extra de ' . $monto . ' de la venta de ' . $reserva;
        }

        return 'editó un abono extra de la venta de ' . $reserva;
    }

    protected $table = 'abonos_extra';

    protected $fillable = [
        'id_venta',
        'monto',
        'fecha_abono',
        'id_tipo_transaccion',
        'folio',
        'user_id',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function tipoTransaccion()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
