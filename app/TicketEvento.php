<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Línea de tiempo visible dentro del ticket (cambios de estado, comentarios, reenvíos).
 * No usa Auditable: TicketService registra en la auditoría que hubo un comentario, sin su texto.
 */
class TicketEvento extends Model
{
    const UPDATED_AT = null;

    const TIPO_ESTADO     = 'estado';
    const TIPO_COMENTARIO = 'comentario';
    const TIPO_REENVIO    = 'reenvio';
    const TIPO_TOMA       = 'toma';       // otro miembro del destino se hizo responsable

    protected $table = 'ticket_eventos';

    protected $fillable = [
        'ticket_id', 'user_id', 'tipo', 'rol_id', 'estado_anterior', 'estado_nuevo', 'comentario',
    ];

    protected $dates = ['created_at'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'ticket_id')->withTrashed();
    }

    /** null = acción del sistema (autocierre). */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Reenvío: departamento al que se reenvió. */
    public function rol()
    {
        return $this->belongsTo(Role::class, 'rol_id');
    }

    public function getEsDelSistemaAttribute()
    {
        return $this->user_id === null;
    }

    /** Frase para la línea de tiempo, sin el sujeto: "Juan" + " lo marcó como solucionado". */
    public function getDescripcionAttribute()
    {
        switch ($this->tipo) {
            case self::TIPO_COMENTARIO:
                return 'comentó';
            case self::TIPO_TOMA:
                return 'pasó a ser responsable';
            case self::TIPO_REENVIO:
                return 'lo reenvió a ' . optional($this->rol)->name;
        }

        if ($this->estado_anterior === null) {
            return 'creó el ticket';
        }

        switch ($this->estado_nuevo) {
            case Ticket::ESTADO_RECEPCIONADO:    return 'lo tomó';
            case Ticket::ESTADO_SOLUCIONADO:     return 'lo marcó como solucionado';
            case Ticket::ESTADO_EN_VERIFICACION: return 'comenzó a revisar la solución';
            case Ticket::ESTADO_RECHAZADO:       return 'indicó que no le corresponde';
            case Ticket::ESTADO_ANULADO:         return 'lo anuló';
            case Ticket::ESTADO_FINALIZADO:
                return $this->es_del_sistema ? 'Se finalizó automáticamente por falta de confirmación' : 'lo finalizó';
            case Ticket::ESTADO_EN_REVISION:
                return in_array($this->estado_anterior, Ticket::ESTADOS_POR_CONFIRMAR) ? 'lo reabrió' : 'comenzó a trabajarlo';
        }

        return 'lo dejó en ' . Ticket::etiquetaEstado($this->estado_nuevo);
    }

    public function getIconoAttribute()
    {
        if ($this->tipo === self::TIPO_COMENTARIO) return 'chat_bubble_outline';
        if ($this->tipo === self::TIPO_TOMA) return 'person_add';
        if ($this->tipo === self::TIPO_REENVIO) return 'forward';
        if ($this->estado_anterior === null) return 'add_circle_outline';
        if ($this->estado_nuevo === Ticket::ESTADO_EN_REVISION && in_array($this->estado_anterior, Ticket::ESTADOS_POR_CONFIRMAR)) return 'replay';

        $porEstado = [
            Ticket::ESTADO_RECEPCIONADO => 'pan_tool', Ticket::ESTADO_EN_REVISION => 'build',
            Ticket::ESTADO_SOLUCIONADO => 'check', Ticket::ESTADO_EN_VERIFICACION => 'visibility',
            Ticket::ESTADO_FINALIZADO => 'done_all', Ticket::ESTADO_RECHAZADO => 'block', Ticket::ESTADO_ANULADO => 'delete',
        ];

        return $porEstado[$this->estado_nuevo] ?? 'info_outline';
    }
}
