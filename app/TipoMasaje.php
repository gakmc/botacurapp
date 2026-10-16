<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class TipoMasaje extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'el tipo de masaje';

    protected $auditoriaCampos = [
        'nombre'              => 'Nombre',
        'id_categoria_masaje' => ['Categoría', 'App\CategoriaMasaje@nombre'],
        'activo'              => ['Activo', 'bool'],
    ];

    protected $table = 'tipos_masajes';
    protected $fillable = ['id_categoria_masaje','nombre','slug','activo'];

    public function categoria()
    {
        return $this->belongsTo(CategoriaMasaje::class, 'id_categoria_masaje');
    }

    public function precios()
    {
        return $this->hasMany(PrecioTipoMasaje::class, 'id_tipo_masaje');
    }

    public function scopeActivos($q){ return $q->where('activo', 1); }
    public function scopeInactivos($q){ return $q->where('activo', 0); }
}
