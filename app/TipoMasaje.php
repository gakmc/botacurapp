<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TipoMasaje extends Model
{
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
}
