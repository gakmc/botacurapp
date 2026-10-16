<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class VentaDirecta extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'fecha'               => ['Fecha', 'fecha'],
        'subtotal'            => ['Subtotal', 'monto'],
        'tiene_propina'       => ['Con propina', 'bool'],
        'valor_propina'       => ['Propina', 'monto'],
        'total'               => ['Total', 'monto'],
        'id_tipo_transaccion' => ['Medio de pago', 'App\TipoTransaccion@nombre'],
        'id_user'             => ['Vendedor', 'App\User@name'],
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' la venta directa #' . $this->id
            . ' por ' . app(\App\Services\AuditoriaService::class)->monto($this->total);
    }

    protected function auditoriaContexto()
    {
        return $this;
    }

    protected $table = 'ventas_directas';

    protected $dates = [
        'fecha',
    ];
    
    protected $fillable = [
        'fecha',
        'tiene_propina',
        'valor_propina',
        'subtotal',
        'total',
        'id_tipo_transaccion',
        'id_user',
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($ventaDirecta) {
            // Eliminar detalles asociados
            $ventaDirecta->detalles()->delete();

            // Eliminar propina si existe
            if ($ventaDirecta->propina) {
                $ventaDirecta->propina->delete();
            }
        });
    }


    public function tipoTransaccion()
    {
        return $this->belongsTo(TipoTransaccion::class, 'id_tipo_transaccion');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVentaDirecta::class);
    }

    public function propina()
    {
        return $this->morphOne(Propina::class, 'propinable');
    }
}
