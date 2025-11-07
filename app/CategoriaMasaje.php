<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CategoriaMasaje extends Model
{
    protected $table = 'categorias_masajes';
    protected $fillable = ['nombre','slug'];

    public function tipos()
    {
        return $this->hasMany(TipoMasaje::class, 'id_categoria_masaje');
    }
}
