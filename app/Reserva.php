<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    protected $fillable = [
        'cantidad_personas', 'fecha_visita', 'descripcion'
    ];


    //RELACIONES
        public function servicios()
        {
            return $this->belongsToMany('App\Servicio');
        }

        public function programas()
        {
            return $this->belongsToMany('App\Programa');
        }

        public function cliente() {
            return $this->belongsTo('App\Cliente');
        }
  

//ALMACENAMIENTO

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
    
}