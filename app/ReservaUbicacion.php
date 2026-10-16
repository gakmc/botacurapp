<?php

namespace App;

use App\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class ReservaUbicacion extends Model
{
    use Auditable;

    protected $auditoriaCategoria = Auditoria::CATEGORIA_OPERACIONAL;

    protected function auditoriaContexto()
    {
        return $this->reserva;
    }

    protected $auditoriaCampos = [
        'id_ubicacion'      => ['Ubicación', 'App\Ubicacion@nombre'],
        'cantidad_personas' => 'Personas',
        'rol'               => 'Rol',
    ];

    protected function auditoriaDescripcion($evento)
    {
        $ubicacion = optional($this->ubicacion)->nombre ?: 'ubicación #' . $this->id_ubicacion;

        return $this->segunEvento($evento, 'asignó', 'editó', 'quitó') . ' la ubicación ' . $ubicacion
            . ($evento === Auditoria::EVENTO_ELIMINO ? ' de ' : ' a ') . $this->textoReservaAuditoria($this->reserva);
    }

    protected $table = 'reserva_ubicaciones';

    protected $fillable = [
        'id_reserva',
        'id_ubicacion',
        'cantidad_personas',
        'rol',
    ];

    public function reserva()
    {
        return $this->belongsTo(Reserva::class, 'id_reserva');
    }

    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'id_ubicacion');
    }
}
