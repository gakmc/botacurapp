<?php

namespace App;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use RealRashid\SweetAlert\Facades\Alert;

class Programa extends Model
{
    protected $guarded = [];  

//RELACIONES
    // public function servicios()
    // {
    //     return $this->hasMany('App\Servicio');
    // }

    public function servicios()
    {
        return $this->belongsToMany(Servicio::class, 'programa_servicio', 'id_programa', 'id_servicio')->withTimestamps();
    }

    public function reservas()
    {
        return $this->hasMany(Reserva::class, 'id_programa');
    }

    public function cotizacionItems()
    {
        return $this->morphMany(CotizacionItem::class, 'itemable');
    }

    
    //ALMACENAMIENTO

    public function store($request)
    {

        $slug = Str::slug($request->nombre_programa, '-');


        $programa = self::create([
            'nombre_programa' => $request->input('nombre_programa'),
            'valor_programa' => $request->input('valor_programa'),
            'descuento' => $request->input('descuento'),
            'slug' => $slug,
        ]);

        if ($request->has('servicios')) {
            $programa->servicios()->sync($request->servicios);
        }

        Alert::success('Éxito', 'Programa guardado')->showConfirmButton();
        return $programa;
    }



    public function my_update($request)
    {
        $slug = Str::slug($request->nombre_programa, '-');

        $this->update($request->except('servicios') + [
            'slug' => $slug
        ]);

        if ($request->has('servicios')) {
            $this->servicios()->sync($request->servicios);
        } else {
            $this->servicios()->sync([]);
        }
        

        Alert::success('Éxito', 'Programa actualizado')->showConfirmButton();
        return $this;
        
    }

    //VALIDACION
    public function getIncluyeMasajesAttribute()
    {
        return $this->servicios->contains(function($servicio){
            return in_array($servicio->nombre_servicio, ['Masaje', 'masaje', 'Masajes', 'masajes']);
        });
    }

    public function getIncluyeAlmuerzosAttribute()
    {
        return $this->servicios->contains(function ($servicio) {
            return in_array(strtolower($servicio->nombre_servicio), ['almuerzo', 'almuerzos']);
        });
    }

    //RECUPERACION DE INFORMACION

    //OTRAS OPERACIONES

}
