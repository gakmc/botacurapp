<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class CotizacionItem extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'cantidad'   => 'Cantidad',
        'valor_neto' => ['Valor neto', 'monto'],
        'total'      => ['Total', 'monto'],
    ];

    public function nombreItemAuditoria()
    {
        $item = $this->itemable;

        return $item ? ($item->nombre_programa ?? $item->nombre_servicio ?? $item->nombre ?? class_basename($item) . ' #' . $item->getKey()) : 'ítem';
    }

    protected function auditoriaDescripcion($evento)
    {
        $detalle = $this->cantidad . ' x ' . $this->nombreItemAuditoria();

        return $this->segunEvento($evento, 'agregó ' . $detalle . ' a', 'editó ' . $detalle . ' en', 'quitó ' . $detalle . ' de')
            . ' la cotización #' . $this->cotizacion_id;
    }

    protected function auditoriaContexto()
    {
        return $this->cotizacion;
    }

    protected $table = 'cotizacion_items';

    protected $fillable = [
        'cotizacion_id', 'itemable_id', 'itemable_type', 'cantidad', 'valor_neto', 'total'
    ];


    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class,'cotizacion_id');
    }

    public function itemable()
    {
        return $this->morphTo();
    }

}
