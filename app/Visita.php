<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Visita extends Model
{
    protected $fillable = [
        'horario_sauna',
        'horario_tinaja',
        'horario_masaje',
        'tipo_masaje',
        'trago_cortesia',
        'observacion',
        'id_reserva',
        'id_ubicacion',
        'id_lugar_masaje',
    ];

    //RELACIONES

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion');
    }


    //ALMACENAMIENTO

    //VALIDACION


    //RECUPERACION DE INFORMACION
    // public function getFechaVisitaAttribute($value)
    // {
    //     return Carbon::parse($value)->format('d-m-Y');
    // }

    public function getHorarioSaunaAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }
    public function getHorarioTinajaAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }
    public function getHorarioMasajeAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('H:i') : null;
    }

    public function getHoraFinSaunaAttribute()
    {
        return $this->calcularHoraFin($this->horario_sauna, ['Sauna', 'Saunas']);
    }

    public function getHoraFinTinajaAttribute()
    {
        return $this->calcularHoraFin($this->horario_tinaja, ['Tinaja', 'Tinajas']);
    }

    // public function getHoraFinMasajeAttribute()
    // {
    //     return $this->calcularHoraFin($this->horario_masaje, ['Masaje', 'Masajes']);
    // }

    // public function getHoraFinMasajeExtraAttribute()
    // {
    //     return $this->calcularHoraFinMasajeExtra($this->horario_masaje);
    // }

    private function calcularHoraFin($horarioInicio, $nombreServicio)
    {

        $servicio = $this->reserva->programa->servicios->first(function ($servicio) use ($nombreServicio) {
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

    //OTRAS OPERACIONES
}
