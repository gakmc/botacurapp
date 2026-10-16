<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PrecioTipoMasaje extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'id_tipo_masaje'     => ['Tipo de masaje', 'App\TipoMasaje@nombre'],
        'duracion_minutos'   => 'Duración (min)',
        'precio_unitario'    => ['Precio individual', 'monto'],
        'precio_pareja'      => ['Precio pareja', 'monto'],
        'pago_masoterapeuta' => ['Pago masoterapeuta', 'monto'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        $tipo = app(\App\Services\AuditoriaService::class)->nombreRelacionado(TipoMasaje::class, 'nombre', $this->id_tipo_masaje);

        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' el precio del masaje ' . $tipo . ' (' . $this->duracion_minutos . ' min)';
    }

    protected $table = 'precios_tipos_masajes';
    protected $fillable = ['id_tipo_masaje','duracion_minutos','precio_unitario','precio_pareja','pago_masoterapeuta'];

    public function tipo()
    {
        return $this->belongsTo(TipoMasaje::class, 'id_tipo_masaje');
    }
}
