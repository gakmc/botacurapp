<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'el proveedor';

    protected $auditoriaCampos = ['nombre' => 'Nombre'];

    // Solo se registra que cambiaron
    protected $auditoriaCamposOcultos = [
        'rut'      => 'Identificación (RUT)',
        'telefono' => 'Teléfono',
        'correo'   => 'Correo',
    ];

    protected $table = 'proveedores';

    protected $fillable = [
        'nombre',
        'rut',
        'telefono',
        'correo',
        'subcategoria_id'
    ];

    public function egresos()
    {
        return $this->hasMany(Egreso::class);
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }
}
