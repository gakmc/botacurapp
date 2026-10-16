<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class DetalleVentaDirecta extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'producto_id'     => ['Producto', 'App\Producto@nombre'],
        'cantidad'        => 'Cantidad',
        'precio_unitario' => ['Precio unitario', 'monto'],
        'subtotal'        => ['Subtotal', 'monto'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $detalle = $this->cantidad . ' x ' . optional($this->producto)->nombre
            . ' (' . app(\App\Services\AuditoriaService::class)->monto($this->subtotal) . ')';

        return $this->segunEvento($evento, 'agregó ' . $detalle . ' a', 'editó el producto ' . optional($this->producto)->nombre . ' en', 'quitó ' . $detalle . ' de')
            . ' la venta directa #' . $this->venta_directa_id;
    }

    protected function auditoriaContexto()
    {
        return $this->ventaDirecta;
    }

    protected $table = 'detalles_ventas_directas';

    protected $fillable = [
        'venta_directa_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function ventaDirecta()
    {
        return $this->belongsTo(VentaDirecta::class, 'venta_directa_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
