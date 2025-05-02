<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VentaDirecta extends Model
{
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
