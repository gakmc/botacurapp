<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LugarMasaje extends Model
{
    protected $table = 'lugares_masajes';
    protected $fillable = [
        'nombre'
    ];


    public function visitas(){
        return $this->hasMany(Visita::class, 'id_lugar_masaje');
    }
}
