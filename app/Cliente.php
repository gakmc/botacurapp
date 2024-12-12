<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use RealRashid\SweetAlert\Facades\Alert;

class Cliente extends Model
{
    protected $fillable = [
        'nombre_cliente', 'whatsapp_cliente','instagram_cliente','sexo','correo'
    ];




//RELACIONES
public function reservas()
{
    return $this->hasMany('App\Reserva');
}



//ALMACENAMIENTO

public function store($request){
    if ($request->has('whatsapp_cliente')) {
        $whatsapp = str_replace('+','',$request->whatsapp_cliente);

        if (substr($whatsapp,0,2) !== '56') {
            $whatsapp = '56'.$whatsapp;
        }

        $request->merge(['whatsapp_cliente' => $whatsapp]);
    }

    Alert::success('Exito', 'Cliente guardado')->showConfirmButton();
    return self::create($request->all());
}

public function my_update($request)
{
    Alert::success('Éxito', 'Cliente actualizado')->showConfirmButton();
    return self::update($request->all());
    
}

//VALIDACION

//RECUPERACION DE INFORMACION

//OTRAS OPERACIONES
    
}
