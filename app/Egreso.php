<?php

namespace App;

use App\Concerns\Auditable;
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

    protected $table = 'egresos';

    protected $fillable = [
        'tipo_documento_id',
        'categoria_id',
        'subcategoria_id',
        'proveedor_id',
        'descripcion',
        'fecha_egreso',
        'numero_documento',
        'total',
        'neto',
        'iva',
        'fuente',
        'periodo_sii',
        'estado',
        'observaciones',
    ];


    public function tipo_documento()
    {
        return $this->belongsTo(TipoDocumento::class, 'tipo_documento_id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaCompra::class, 'categoria_id');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Subcategoria::class, 'subcategoria_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function pagos()
    {
        return $this->hasMany(PagoEgreso::class, 'egreso_id');
    }

    public function getFechaEgresoAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('d-m-Y') : null;
    }
}
