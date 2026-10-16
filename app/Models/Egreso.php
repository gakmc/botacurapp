<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Auditoria;
use Illuminate\Database\Eloquent\Model;

class Egreso extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaCampos = [
        'descripcion'       => 'Descripción',
        'tipo_documento_id' => ['Tipo de documento', 'App\TipoDocumento@nombre'],
        'numero_documento'  => 'N° documento',
        'proveedor_id'      => ['Proveedor', 'App\Proveedor@nombre'],
        'categoria_id'      => ['Categoría', 'App\CategoriaCompra@nombre'],
        'subcategoria_id'   => ['Subcategoría', 'App\Subcategoria@nombre'],
        'fecha_egreso'      => ['Fecha', 'fecha'],
        'neto'              => ['Neto', 'monto'],
        'iva'               => ['IVA', 'monto'],
        'total'             => ['Total', 'monto'],
        'periodo_sii'       => 'Período SII',
        'fuente'            => 'Origen',
        'estado'            => 'Estado',
        'observaciones'     => 'Observaciones',
    ];

    protected function auditoriaDescripcion($evento)
    {
        return $this->segunEvento($evento, 'registró', 'editó', 'eliminó') . ' el egreso #' . $this->id
            . ($this->descripcion ? ' "' . \Illuminate\Support\Str::limit($this->descripcion, 60) . '"' : '')
            . ' por ' . app(\App\Services\AuditoriaService::class)->monto($this->total);
    }

    protected $connection = 'mysql';
    protected $table = 'egresos';

    protected $fillable = [
        'descripcion',
        'total',
        'fecha_egreso',
        'numero_documento',
        'metodo_pago',
        'estado',
        'fuente',
        'observaciones',
    ];

    protected $casts = [
        'total' => 'integer',
        'fecha_egreso' => 'date',
    ];
}
