<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class DetalleConsumo extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected function auditoriaContexto()
    {
        return optional(optional($this->consumo)->venta)->reserva;
    }

    protected $auditoriaCampos = [
        'id_producto'       => ['Producto', 'App\Producto@nombre'],
        'cantidad_producto' => 'Cantidad',
        'subtotal'          => ['Subtotal', 'monto'],
        'genera_propina'    => ['Genera propina', 'bool'],
        'estado'            => 'Estado',
        'observacion'       => 'Observación',
    ];

    protected function auditoriaDescripcion($evento)
    {
        $producto = optional($this->producto)->nombre ?: 'producto #' . $this->id_producto;
        $reserva  = $this->textoReservaAuditoria(optional(optional($this->consumo)->venta)->reserva);
        $detalle  = $this->cantidad_producto . ' x ' . $producto . ' (' . app(\App\Services\AuditoriaService::class)->monto($this->subtotal) . ')';

        if ($evento === Auditoria::EVENTO_CREO) {
            return 'agregó el pedido ' . $detalle . ' al consumo de ' . $reserva;
        }
        if ($evento === Auditoria::EVENTO_ELIMINO) {
            return 'eliminó el pedido ' . $detalle . ' del consumo de ' . $reserva;
        }

        return 'editó el pedido de ' . $producto . ' en el consumo de ' . $reserva;
    }

    protected $table = 'detalles_consumos';
    
    protected $fillable = [
        'cantidad_producto',
        'subtotal',
        'genera_propina',
        'id_consumo',
        'id_producto',
        'estado',
        'observacion'
    ];
    

    public function consumo()
    {
        return $this->belongsTo(Consumo::class, 'id_consumo');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    
}
