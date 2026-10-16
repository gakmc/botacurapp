<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Auditoria;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla gas_compras (BD principal: botacurapp).
 * Registra cada compra/pago de cilindros de gas al proveedor.
 */
class GasCompra extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'proveedor_nombre'   => 'Proveedor',
        'fecha_compra'       => ['Fecha', 'fecha'],
        'cantidad_cilindros' => 'Cilindros',
        'valor_unitario_clp' => ['Valor unitario', 'monto'],
        'total_clp'          => ['Total', 'monto'],
        'estado'             => 'Estado',
        'origen'             => 'Origen',
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' la compra de gas de ' . $this->cantidad_cilindros
            . ' cilindro(s) por ' . app(\App\Services\AuditoriaService::class)->monto($this->total_clp);
    }

    protected $connection = 'mysql';   // BD principal (botacurapp)
    protected $table      = 'gas_compras';

    protected $fillable = [
        'proveedor_id',
        'proveedor_nombre',
        'fecha_compra',
        'valor_unitario_clp',
        'cantidad_cilindros',
        'kg_cilindro',
        'total_clp',
        'documento',
        'observacion',
        'egreso_id',
        'origen',
        'estado',
    ];

    protected $casts = [
        'fecha_compra'       => 'date',
        'valor_unitario_clp' => 'integer',
        'cantidad_cilindros' => 'integer',
        'kg_cilindro'        => 'decimal:2',
        'total_clp'          => 'integer',
    ];

    /**
     * Calcula el total automáticamente antes de guardar.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $model->total_clp = $model->valor_unitario_clp * $model->cantidad_cilindros;
        });
    }

    /**
     * Relación con el egreso creado automáticamente.
     */
    public function egreso()
    {
        return $this->belongsTo(Egreso::class, 'egreso_id');
    }
}
