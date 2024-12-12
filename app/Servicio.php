<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Servicio extends Model
{
    protected $fillable = [
        'nombre_servicio', 'valor_servicio', 'costo_servicio', 'duracion',
    ];

//RELACIONES
    // public function programa()
    // {
    //     return $this->belongsTo('App\Programa');
    // }

    public function programas()
    {
        return $this->belongsToMany(Programa::class, 'programa_servicio', 'id_servicio', 'id_programa')->withTimestamps();
    }

    public function detalleServicioExtra()
    {
        return $this->hasMany(DetalleServiciosExtra::class, 'id_servicio_extra');
    }

//ALMACENAMIENTO

    // Mutador para convertir los minutos en formato HH:MM:SS
    public function setDuracionAttribute($value)
    {
        $minutes = (int) $value;

        $hours = floor($value / 60);
        $minutes = $value % 60;
        $seconds = 0;

        $this->attributes['duracion'] = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    // Mutador para asegurarse de que costo_servicio se guarde como null si está vacío
    public function setCostoServicioAttribute($value)
    {
        $this->attributes['costo_servicio'] = $value !== '' ? $value : null;
    }

//VALIDACION

//RECUPERACION DE INFORMACION

    // Accessor para obtener la duración en minutos
    public function getDuracionAttribute($value)
    {
        // Asimilando que $value está en formato HH:MM:SS (gracias al mutador)
        list($hours, $minutes, $seconds) = explode(':', $value);

        // Convertir a minutos
        return ($hours * 60) + $minutes;
    }

//OTRAS OPERACIONES

}
