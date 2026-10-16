<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PoroDetalleVenta extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'poro_id'         => ['Producto', 'App\PoroPoro@nombre'],
        'cantidad'        => 'Cantidad',
        'precio_unitario' => ['Precio unitario', 'monto'],
        'subtotal'        => ['Subtotal', 'monto'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $detalle = $this->cantidad . ' x ' . optional($this->poro)->nombre;

        return $this->segunEvento($evento, 'agregó ' . $detalle . ' a', 'editó ' . $detalle . ' en', 'quitó ' . $detalle . ' de')
            . ' la venta PoroPoro #' . $this->poro_venta_id;
    }

    protected function auditoriaContexto()
    {
        return $this->venta;
    }

    protected $table = 'poro_detalle_ventas';

    protected $fillable = [
        'poro_venta_id',
        'poro_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    // Cada detalle pertenece a una venta
    public function venta()
    {
        return $this->belongsTo(PoroPoroVenta::class, 'poro_venta_id');
    }

    // Cada detalle se refiere a un poro
    public function poro()
    {
        return $this->belongsTo(PoroPoro::class, 'poro_id');
    }
}
