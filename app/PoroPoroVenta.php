<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PoroPoroVenta extends Model
{
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
