<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Masaje extends Model
{
    protected $table = 'masajes';

    protected $fillable = [
        'horario_masaje', 'tipo_masaje', 'id_lugar_masaje', 'persona', 'tiempo_extra', 'id_reserva', 'user_id',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
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
        return $this->calcularHoraFin($this->horario_masaje, $this->tiempo_extra);
    }

    public function getHoraFinMasajeExtraAttribute()
    {
        return $this->calcularHoraFinMasajeExtra($this->horario_masaje, $this->tiempo_extra);
    }

    public function getHoraFinalMasajeAttribute()
    {
        $nombreServicio = ['Masaje', 'Masajes', 'masaje', 'masajes'];

        $servicio = Servicio::whereIn('nombre_servicio', $nombreServicio)->first();

        if ($this->horario_masaje) {
            if ($this->tiempo_extra) {
                return Carbon::parse($this->horario_masaje)->addMinutes($servicio->duracion*2)->format('H:i');
            }else{
                return Carbon::parse($this->horario_masaje)->addMinutes($servicio->duracion)->format('H:i');
            }
        }
        return null;
    }

    private function calcularHoraFin($horarioInicio, $tiempoExtra)
    {
        $nombreServicio = ['Masaje', 'Masajes', 'masaje', 'masajes'];

        $servicio = Servicio::whereIn('nombre_servicio', $nombreServicio)->first();
        if ($horarioInicio && $servicio) {
            $horaInicio = Carbon::parse($horarioInicio);
            if ($tiempoExtra) {
                return $horaInicio->addMinutes($servicio->duracion*2)->format('H:i');
            }else{
                return $horaInicio->addMinutes($servicio->duracion)->format('H:i');
            }
        }

        return null;
    }

    private function calcularHoraFinMasajeExtra($horarioInicio, $tiempoExtra)
    {
        $nombreServicio = ['Masaje', 'Masajes'];

        $servicio = Servicio::whereIn('nombre_servicio', $nombreServicio)->first();

        if ($horarioInicio && $servicio) {
            $horaInicio = Carbon::parse($horarioInicio);
            if ($tiempoExtra) {
                return $horaInicio->addMinutes($servicio->duracion*2)->format('H:i');
            }else{
                return $horaInicio->addMinutes($servicio->duracion)->format('H:i');
            }
        }

        return null;
    }

}
