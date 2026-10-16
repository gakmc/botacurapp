<?php
namespace App;

use Carbon\Carbon;
use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected function auditoriaContexto()
    {
        return optional($this->venta)->reserva;
    }

    // Sin correo ni teléfono: datos personales del comprador.
    protected $auditoriaCampos = [
        'codigo'            => 'Código',
        'monto'             => ['Monto', 'monto'],
        'id_programa'       => ['Programa', 'App\Programa@nombre_programa'],
        'cantidad_personas' => 'Personas',
        'de'                => 'De',
        'para'              => 'Para',
        'validez_hasta'     => ['Válida hasta', 'fecha'],
        'usada'             => ['Usada', 'bool'],
        'fecha_uso'         => ['Fecha de uso', 'fecha'],
        'id_venta'          => 'Venta',
    ];

    protected function auditoriaDescripcion($evento)
    {
        $monto = app(\App\Services\AuditoriaService::class)->monto($this->monto);

        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' la gift card ' . $this->codigo
            . ($evento === Auditoria::EVENTO_CREO ? ' por ' . $monto : '');
    }

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
