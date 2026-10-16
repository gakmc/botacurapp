<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Subcategoria extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'la subcategoría de compras';

    protected $auditoriaCampos = [
        'nombre'       => 'Nombre',
        'categoria_id' => ['Categoría', 'App\CategoriaCompra@nombre'],
    ];

    protected $table = 'subcategorias_compras';

    protected $fillable = [
        'nombre',
        'categoria_id'
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaCompra::class, 'categoria_id');
    }

    public function egresos()
    {
        return $this->hasMany(Egreso::class);
    }
}
