<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class CategoriaMasaje extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'la categoría de masaje';

    protected $auditoriaCampos = ['nombre' => 'Nombre'];

    protected $table = 'categorias_masajes';
    protected $fillable = ['nombre','slug'];

    public function tipos()
    {
        return $this->hasMany(TipoMasaje::class, 'id_categoria_masaje');
    }
}
