<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use RealRashid\SweetAlert\Facades\Alert;

class Reserva extends Model
{
    protected $dates = ['fecha_visita'];


    protected $fillable = [
        'cantidad_personas', 'fecha_visita', 'descripcion', 'cliente_id',
        'cantidad_masajes',
        'observacion',
        'id_programa',
        'user_id',
    ];

    //RELACIONES

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa');
    }

    public function reagendamientos()
    {
        return $this->hasMany(Reagendamiento::class, 'id_reserva');
    }
    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function venta()
    {
        return $this->hasOne(Venta::class, 'id_reserva');
    }
    
    public function visitas()
    {
        return $this->hasMany(Visita::class, 'id_reserva');
    }
//ALMACENAMIENTO

    public function store($request)
    {
        if ($request->has('cliente_id')) {
            $cliente = $request->cliente_id;
            $request->merge(['cliente_id' => $cliente]);
        }

        $user_id = auth()->id();

        $request->merge(['user_id' => $user_id]);

        Alert::success('Exito', 'Reserva Realizada', 'Confirmar')->showConfirmButton();
        return self::create($request->all());
    }

//VALIDACION

//RECUPERACION DE INFORMACION
    public function getFechaVisitaAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }



    // public function getHoraFinAttribute()
    // {
    //     // Identifica el servicio específico, reemplaza $idServicio con el identificador correcto
    //     $servicioEspecifico = $this->programa->servicios->firstWhere('id_servicio', $servicio->id);

    //     if ($this->horario_sauna && $servicioEspecifico) {
    //         $duracion = $servicioEspecifico->duracion;
    //         $horaInicio = Carbon::parse($this->horario_sauna);
    //         $horaFin = $horaInicio->addMinutes($duracion);
    //         return $horaFin->format('H:i A');
    //     }

    //     return null;
    // }

    // public function HoraFin($inicio, $duracion)
    // {
    //     // Asegúrate de que haya un horario_inicio y una duración de servicio
    //     if ($inicio && $duracion) {
    //         // Convierte el horario_inicio en una instancia de Carbon
    //         $horaInicio = Carbon::parse($inicio);
            
    //         // Suma la duración del servicio en minutos
    //         $horaFin = $horaInicio->addMinutes($duracion);

    //         // Devuelve la hora fin en el formato deseado
    //         return $horaFin->format('H:i A');
    //     }

    //     return null;
    // }

//OTRAS OPERACIONES


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

    $servicio = $this->programa->servicios->first(function ($servicio) use ($nombreServicio) {
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
