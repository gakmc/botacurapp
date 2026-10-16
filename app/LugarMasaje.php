<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class LugarMasaje extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected $auditoriaNombre = 'el lugar de masaje';

    protected $auditoriaCampos = ['nombre' => 'Nombre'];

    protected $table = 'lugares_masajes';
    protected $fillable = [
        'nombre'
    ];


    public function masajes(){
        return $this->hasMany(Masaje::class, 'id_lugar_masaje');
    }
}
