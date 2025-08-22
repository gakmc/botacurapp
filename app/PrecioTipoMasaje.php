<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PrecioTipoMasaje extends Model
{
        protected $table = 'precios_tipos_masajes';
    protected $fillable = ['id_tipo_masaje','duracion_minutos','precio_unitario','precio_pareja'];

    public function tipo()
    {
        return $this->belongsTo(TipoMasaje::class, 'id_tipo_masaje');
    }
}
