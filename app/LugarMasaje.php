<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LugarMasaje extends Model
{
    protected $table = 'lugares_masajes';
    protected $fillable = [
        'nombre'
    ];


    public function masajes(){
        return $this->hasMany(Masaje::class, 'id_lugar_masaje');
    }
}
