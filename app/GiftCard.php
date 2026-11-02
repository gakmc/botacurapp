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
        'fecha_compra', // 15/10/2026: vence 45 dias despues
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

    /** Dias de vigencia desde la compra (15/10/2026). */
    const DIAS_VIGENCIA = 45;

    public static function venceDesde($fechaCompra): string
    {
        return Carbon::parse($fechaCompra)->addDays(self::DIAS_VIGENCIA)->toDateString();
    }

    /** Fecha de compra: la registrada o, si no hay, el dia en que se creo. */
    public function getFechaCompraRealAttribute(): ?Carbon
    {
        if ($this->fecha_compra) {
            return Carbon::parse($this->fecha_compra);
        }
        return $this->created_at ? Carbon::parse($this->created_at)->startOfDay() : null;
    }

    /** Vencimiento: validez_hasta o, si no hay, compra + 45 dias. */
    public function getVenceAttribute(): ?Carbon
    {
        if ($this->validez_hasta) {
            return Carbon::parse($this->validez_hasta);
        }
        return $this->fecha_compra_real ? $this->fecha_compra_real->copy()->addDays(self::DIAS_VIGENCIA) : null;
    }

    public function getCompradaAttribute(){
        return $this->fecha_compra_real ? $this->fecha_compra_real->format('d-m-Y') : '';
    }

    public function getValidoAttribute(){
        return $this->vence ? $this->vence->format('d-m-Y') : '';
    }

    /** Dias que quedan (negativo = vencida). */
    public function getDiasRestantesAttribute(): ?int
    {
        return $this->vence ? (int) Carbon::today()->diffInDays($this->vence->copy()->startOfDay(), false) : null;
    }

    /** Texto y color para mostrar la vigencia. */
    public function getVigenciaAttribute(): array
    {
        if ($this->usada) {
            return ['texto' => 'Usada', 'color' => 'grey'];
        }
        $d = $this->dias_restantes;
        if ($d === null) {
            return ['texto' => 'Sin fecha', 'color' => 'grey'];
        }
        if ($d < 0) {
            return ['texto' => 'Vencida hace ' . abs($d) . ' día' . (abs($d) === 1 ? '' : 's'), 'color' => 'red'];
        }
        if ($d === 0) {
            return ['texto' => 'Vence hoy', 'color' => 'orange'];
        }
        return ['texto' => 'Quedan ' . $d . ' día' . ($d === 1 ? '' : 's'), 'color' => $d <= 7 ? 'orange' : 'green'];
    }
}
