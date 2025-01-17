<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Masaje extends Model
{
    protected $table = 'masajes';

    protected $fillable = [
        'horario_masaje', 'tipo_masaje', 'id_lugar_masaje', 'persona', 'id_visita', 'user_id',
    ];

    public function visita()
    {
        return $this->belongsTo(Visita::class, 'id_visita');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lugarMasaje()
    {
        return $this->belongsTo(LugarMasaje::class, 'id_lugar_masaje');
    }









    public function getHorarioMasajeAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }
    public function getHoraFinMasajeAttribute()
    {
        return $this->calcularHoraFin($this->horario_masaje, ['Masaje', 'Masajes']);
    }

    public function getHoraFinMasajeExtraAttribute()
    {
        return $this->calcularHoraFinMasajeExtra($this->horario_masaje);
    }

    private function calcularHoraFin($horarioInicio, $nombreServicio)
    {
        $visita = $this->visita;

        $servicio = $visita->reserva->programa->servicios->first(function ($servicio) use ($nombreServicio) {
            return in_array($servicio->nombre_servicio, $nombreServicio);
        });
        if ($horarioInicio && $servicio) {
            $horaInicio = Carbon::parse($horarioInicio);
            return $horaInicio->addMinutes($servicio->duracion)->format('H:i');
        }

        return null;
    }

    private function calcularHoraFinMasajeExtra($horarioInicio)
    {
        $nombreServicio = ['Masaje', 'Masajes'];

        $servicio = Servicio::whereIn('nombre_servicio', $nombreServicio)->first();

        if ($horarioInicio && $servicio) {
            $horaInicio = Carbon::parse($horarioInicio);
            return $horaInicio->addMinutes($servicio->duracion)->format('H:i');
        }

        return null;
    }

}
