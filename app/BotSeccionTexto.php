<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BotSeccionTexto extends Model
{
    protected $table = 'bot_secciones_texto';
    protected $fillable = ['clave', 'categoria', 'etiqueta', 'contenido', 'orden', 'updated_by'];

    public function scopeCategoria($q, $categoria)
    {
        return $q->where('categoria', $categoria)->orderBy('orden');
    }

    /**
     * Devuelve el contenido de una clave, o un texto de aviso si no existe
     * (para que un getSystemPrompt() nunca reciba null y rompa el prompt).
     */
    public static function contenidoDe($clave, $default = '')
    {
        $seccion = static::where('clave', $clave)->first();
        return $seccion ? $seccion->contenido : $default;
    }
}
