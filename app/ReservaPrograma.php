<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Detalle de programas de una reserva (15/10/2026): una reserva puede llevar varios
 * programas, p. ej. 2 Wellness Day + 1 Full Day. reservas.id_programa sigue siendo el
 * programa principal y reservas.cantidad_personas el total de personas.
 * Las reservas antiguas (o del bot / WooCommerce) no tienen filas aqui: se leen como
 * un solo programa.
 */
class ReservaPrograma extends Model
{
    protected $table = 'reserva_programas';

    protected $fillable = ['id_reserva', 'id_programa', 'cantidad_personas', 'valor_unitario', 'principal'];

    protected $casts = ['principal' => 'boolean'];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class, 'id_programa');
    }
}
