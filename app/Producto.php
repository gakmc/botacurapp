<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_FINANCIERA;

    protected $auditoriaNombre = 'el producto';

    protected $auditoriaCampos = [
        'nombre'           => 'Nombre',
        'valor'            => ['Precio', 'monto'],
        'estado'           => 'Estado',
        'id_tipo_producto' => ['Tipo de producto', 'App\TipoProducto@nombre'],
    ];

    protected $table = 'productos';

    protected $fillable = [
        'nombre',
        'valor',
        'estado',
        'id_tipo_producto'
    ];

    public function insumos()
    {
        return $this->belongsToMany(Insumo::class, 'insumo_producto', 'id_producto', 'id_insumo')
                    ->withPivot('id_unidad_medida', 'cantidad_insumo_usar', 'total_costo_producto', 'utilidad_producto')
                    ->withTimestamps();
    }

    public function detallesConsumos()
    {
        return $this->hasMany(DetalleConsumo::class, 'id_producto');
    }
    
    public function detallesVentasDirectas()
    {
        return $this->hasMany(DetalleVentaDirecta::class, 'id_producto');
    }

    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class , 'id_tipo_producto', 'id');
    }

    public function cotizacionItems()
    {
        return $this->morphMany(CotizacionItem::class, 'itemable');
    }

    public function scopeActivos($q){ return $q->where('estado', 'activo')->orWhereNull('estado'); }
    public function scopeInactivos($q){ return $q->where('estado', 'inactivo'); }


}
