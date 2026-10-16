<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class DetalleServiciosExtra extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected function auditoriaContexto()
    {
        return optional(optional($this->consumo)->venta)->reserva;
    }

    protected $auditoriaCampos = [
        'id_servicio_extra' => ['Servicio', 'App\Servicio@nombre_servicio'],
        'cantidad_servicio' => 'Cantidad',
        'subtotal'          => ['Subtotal', 'monto'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $servicio = optional($this->servicio)->nombre_servicio ?: 'servicio #' . $this->id_servicio_extra;
        $reserva  = $this->textoReservaAuditoria(optional(optional($this->consumo)->venta)->reserva);
        $detalle  = $this->cantidad_servicio . ' x ' . $servicio . ' (' . app(\App\Services\AuditoriaService::class)->monto($this->subtotal) . ')';

        if ($evento === Auditoria::EVENTO_CREO) {
            return 'agregó el servicio extra ' . $detalle . ' a ' . $reserva;
        }
        if ($evento === Auditoria::EVENTO_ELIMINO) {
            return 'eliminó el servicio extra ' . $detalle . ' de ' . $reserva;
        }

        return 'editó el servicio extra ' . $servicio . ' de ' . $reserva;
    }

    protected $table = 'detalle_servicios_extra';

    protected $fillable = [
        'cantidad_servicio',
        'subtotal',
        'id_consumo',
        'id_servicio_extra',
        'id_precio_tipo_masaje'
    ];

    public function consumo()
    {
        return $this->belongsTo(Consumo::class, 'id_consumo');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'id_servicio_extra');
    }

    public function precioTipoMasaje()
    {
        return $this->belongsTo(PrecioTipoMasaje::class, 'id_precio_tipo_masaje');
    }
}
