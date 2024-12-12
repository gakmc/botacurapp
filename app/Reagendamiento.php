<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Reagendamiento extends Model
{
    protected $fillable = [
        "nueva_fecha",
        "fecha_original",
        "id_reserva"

    ];

    //RELACIONES

    public function reserva(){
        return $this->belongsTo(Reserva::class,'id_reserva');
    }


   //RECUPERACION DE INFORMACION
    public function getFechaOriginalAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    public function getNuevaFechaAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }
}
