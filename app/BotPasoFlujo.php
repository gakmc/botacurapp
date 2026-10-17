<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BotPasoFlujo extends Model
{
    protected $table = 'bot_pasos_flujo';
    protected $fillable = ['numero_paso', 'titulo', 'instrucciones', 'orden', 'activo', 'bloqueado', 'updated_by'];
    protected $casts = ['activo' => 'boolean', 'bloqueado' => 'boolean'];

    public function scopeActivos($q)
    {
        return $q->where('activo', 1)->orderBy('orden');
    }
}
