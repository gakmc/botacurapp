<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected function auditoriaContexto()
    {
        return $this->reserva;
    }

    // Sin "alergias": dato de salud (sensible).
    protected $auditoriaCampos = [
        'id_producto_entrada'        => ['Entrada', 'App\Producto@nombre'],
        'id_producto_fondo'          => ['Fondo', 'App\Producto@nombre'],
        'id_producto_acompanamiento' => ['Acompañamiento', 'App\Producto@nombre'],
        'tipo_servicio'              => 'Tipo de servicio',
        'observacion'                => 'Observación',
    ];

    protected function auditoriaDescripcion($evento)
    {
        $persona = $this->numero_persona ? ' (persona ' . $this->numero_persona . ')' : '';

        return $this->segunEvento($evento, 'agregó un menú' . $persona . ' a', 'editó el menú' . $persona . ' de', 'eliminó el menú' . $persona . ' de')
            . ' ' . $this->textoReservaAuditoria($this->reserva);
    }

    protected $fillable = [
        'id_reserva',
        'id_producto_entrada',
        'id_producto_fondo',
        'id_producto_acompanamiento',
        'alergias',
        'observacion',
        'tipo_servicio',
    ];

    // RELACIONES
    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    // Relación con el producto de entrada
    public function productoEntrada()
    {
        return $this->belongsTo(Producto::class, 'id_producto_entrada');
    }

    // Relación con el producto de fondo
    public function productoFondo()
    {
        return $this->belongsTo(Producto::class, 'id_producto_fondo');
    }

    // Relación con el producto de acompañamiento
    public function productoAcompanamiento()
    {
        return $this->belongsTo(Producto::class, 'id_producto_acompanamiento');
    }
}
