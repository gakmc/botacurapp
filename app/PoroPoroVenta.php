<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class PoroPoroVenta extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'fecha'               => ['Fecha', 'fecha'],
        'total'               => ['Total', 'monto'],
        'id_tipo_transaccion' => ['Medio de pago', 'App\TipoTransaccion@nombre'],
        'id_user'             => ['Vendedor', 'App\User@name'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' la venta PoroPoro #' . $this->id
            . ' por ' . app(\App\Services\AuditoriaService::class)->monto($this->total);
    }

    protected function auditoriaContexto()
    {
        return $this;
    }

    /** Detalle legible para AuditoriaService::sincronizar() */
    public function resumenDetallesAuditoria()
    {
        $s = app(\App\Services\AuditoriaService::class);

        return $this->detalles()->with('poro')->orderBy('id')->get()->map(function ($d) use ($s) {
            return $d->cantidad . ' x ' . optional($d->poro)->nombre . ' (' . $s->monto($d->subtotal) . ')';
        })->implode(', ');
    }

    protected $table = 'poro_poro_ventas';

    protected $fillable = [
        'fecha',
        'total',
        'id_tipo_transaccion',
        'id_user',
    ];

    // Relación inversa con el usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relación con tipo de transacción
    public function tipoTransaccion()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }

    // Una venta puede tener muchos detalles
    public function detalles()
    {
        return $this->hasMany(PoroDetalleVenta::class, 'poro_venta_id');
    }
}
