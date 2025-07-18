<?php
namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    protected $table = 'gift_cards';

    protected $fillable = [
        'codigo', //Se generara un codigo automatico unico para identificar la GC
        'monto', //Solicitado al generar la Gift Card
        'usada', //false hasta que se ingrese en una reserva
        'fecha_uso', //Se completara al ingresar la fecha_visita
        'id_programa', //Se asigna el programa solicitado en la GC
        'id_venta', //Este campo se completara al registrar la reserva
        'validez_hasta',
        'de',//✔️
        'para',//✔️
        'correo',//✔️
        'telefono',//✔️
        'cantidad_personas',//✔️
        'generada_por',//✔️
    ];

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa');
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class, 'id_venta');
    }

    public static function generarCodigoUnicoGiftCard($longitud = 8)
    {
        do {
            $codigo = strtoupper(Str::random($longitud));
        } while (GiftCard::where('codigo', $codigo)->exists());

        return $codigo;
    }

    public function getValidoAttribute(){
        return Carbon::parse($this->validez_hasta)->format('d-m-Y');
    }
}
