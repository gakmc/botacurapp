<?php
namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    // Sin correo del cliente
    protected $auditoriaCampos = [
        'cliente'       => 'Cliente',
        'solicitante'   => 'Solicitante',
        'fecha_reserva' => ['Fecha de reserva', 'fecha'],
        'validez_dias'  => 'Validez (días)',
        'observaciones' => 'Observaciones',
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'creó', 'editó', 'eliminó') . ' la cotización #' . $this->id . ' para ' . $this->cliente;
    }

    protected function auditoriaContexto()
    {
        return $this;
    }

    /** Detalle legible para AuditoriaService::sincronizar() */
    public function resumenItemsAuditoria()
    {
        $s = app(\App\Services\AuditoriaService::class);

        return $this->items()->with('itemable')->orderBy('id')->get()->map(function ($i) use ($s) {
            return $i->cantidad . ' x ' . $i->nombreItemAuditoria() . ' (' . $s->monto($i->total) . ')';
        })->implode(', ');
    }

    protected $table = 'cotizaciones';

    protected $fillable = [
        'cliente', 'solicitante', 'fecha_emision', 'fecha_reserva', 'validez_dias', 'correo', 'observaciones', 'iva_incluido'
    ];

    protected $dates = [
        'fecha_emision', 'fecha_reserva'
    ];

    protected $casts = [
        'iva_incluido' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(CotizacionItem::class, 'cotizacion_id');
    }
}
