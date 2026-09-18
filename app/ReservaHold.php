<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReservaHold extends Model
{
    protected $table = 'reserva_holds';

    protected $fillable = [
        'telefono', 'nombre', 'email', 'programa_id', 'fecha', 'personas',
        'masajes_extra', 'desayuno_once', 'desayuno_tipo', 'observacion',
        'tipo_pago', 'valor_total', 'abono_50', 'diferencia', 'estado',
        'expira_en', 'id_reserva', 'datos_json',
    ];

    protected $casts = [
        'fecha'     => 'date',
        'expira_en' => 'datetime',
    ];

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo')->where('expira_en', '>', now());
    }
}
