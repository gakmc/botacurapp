<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InventarioMovimiento extends Model
{
    protected $table = 'inventario_movimientos';

    protected $fillable = [
        'tipo',
        'origen',
        'referencia_id',
        'referencia_type',
        'id_user',
        'id_sector',
        'observacion'

    ];


    public function referencia()
    {
        return $this->morphTo();
    }

    public function detalles()
    {
        return $this->hasMany(InventarioMovimientoDetalle::class, 'movimiento_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class, 'id_sector');
    }

}
