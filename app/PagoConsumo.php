<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PagoConsumo extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected function auditoriaContexto()
    {
        return optional($this->venta)->reserva;
    }

    protected $auditoriaCampos = [
        'valor_consumo'        => ['Valor consumo', 'monto'],
        'pago1'                => ['Pago 1', 'monto'],
        'id_tipo_transaccion1' => ['Medio de pago 1', 'App\TipoTransaccion@nombre'],
        'pago2'                => ['Pago 2', 'monto'],
        'id_tipo_transaccion2' => ['Medio de pago 2', 'App\TipoTransaccion@nombre'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' el pago del consumo de '
            . $this->textoReservaAuditoria(optional($this->venta)->reserva);
    }

    protected $table = 'pago_consumos';
    protected $fillable = [
        'valor_consumo',
        'pago1',
        'pago2',
        'imagen_pago1',
        'imagen_pago2',
        'id_tipo_transaccion1',
        'id_tipo_transaccion2',
        'id_venta',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public function tipoTransaccion1()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }
    
    public function tipoTransaccion2()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }
}
