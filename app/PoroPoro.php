<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PoroPoro extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaNombre = 'el producto PoroPoro';

    protected $auditoriaCampos = [
        'nombre'      => 'Nombre',
        'valor'       => ['Precio', 'monto'],
        'descripcion' => 'Descripción',
    ];

    protected $table = 'poro_poros';

    protected $fillable = [
        'nombre',
        'valor',
        'descripcion',
    ];

    // Un poro puede estar en muchos detalles de venta
    public function detalleVentas()
    {
        return $this->hasMany(PoroDetalleVenta::class, 'poro_id');
    }
}
