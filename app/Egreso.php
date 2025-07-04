<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Egreso extends Model
{
    protected $table = 'egresos';

    protected $fillable = [
        'tipo_documento_id',
        'categoria_id',
        'subcategoria_id',
        'proveedor_id',
        'fecha',
        'neto',
        'iva',
        'total',
        'folio',
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

    public function getFechaAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('d-m-Y');
    }
}
