<?php

namespace App\Policies;

use App\Ticket;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Quién puede hacer qué en un ticket. El modelo decide si la acción procede
 * desde el estado actual y de qué lado es (origen / destino); aquí se decide
 * si el usuario pertenece a ese lado.
 *
 * Supervisión (administrador): ve todos los tickets, pero solo actúa en los de
 * sus roles, salvo anular, que puede hacerlo en cualquiera.
 */
class TicketPolicy
{
    use HandlesAuthorization;

    /** Acciones del destino que, una vez tomado el ticket, solo hace su responsable. */
    const SOLO_RESPONSABLE = ['comenzar', 'solucionar', 'rechazar'];

    /** Estados en los que otro miembro del destino puede tomar un ticket ya asignado. */
    const ESTADOS_TOMABLES = [Ticket::ESTADO_RECEPCIONADO, Ticket::ESTADO_EN_REVISION];

    public function viewAny(User $user)
    {
        return $user->usaTickets();
    }

    public function view(User $user, Ticket $ticket)
    {
        return $user->supervisaTickets() || $ticket->esDelOrigen($user) || $ticket->esDelDestino($user);
    }

    public function create(User $user)
    {
        return $user->rolesTickets()->isNotEmpty();
    }

    /**
     * Uso: $this->authorize('accion', [$ticket, 'finalizar']);
     */
    public function accion(User $user, Ticket $ticket, $accion)
    {
        if (! $ticket->admiteAccion($accion)) {
            return false;
        }

        if ($accion === 'anular') {
            return $ticket->esDelOrigen($user) || $user->supervisaTickets();
        }

        switch (Ticket::ACCIONES[$accion]['lado']) {
            case Ticket::LADO_ORIGEN:
                return $ticket->esDelOrigen($user);

            case Ticket::LADO_DESTINO:
                if (! $ticket->esDelDestino($user)) {
                    return false;
                }
                if (in_array($accion, self::SOLO_RESPONSABLE) && $ticket->responsable_id !== null) {
                    return (int) $ticket->responsable_id === (int) $user->id;
                }

                return true;
        }

        // LADO_SISTEMA (autocierre): nunca un usuario
        return false;
    }

    /** Acciones que el usuario puede hacer ahora sobre el ticket (para mostrar solo esos botones). */
    public function accionesDisponibles(User $user, Ticket $ticket)
    {
        return array_values(array_filter(array_keys(Ticket::ACCIONES), function ($accion) use ($user, $ticket) {
            return $this->accion($user, $ticket, $accion);
        }));
    }

    /** Otro miembro del destino se hace responsable de un ticket ya recepcionado (queda en la línea de tiempo). */
    public function tomar(User $user, Ticket $ticket)
    {
        return $ticket->esDelDestino($user)
            && in_array($ticket->estado, self::ESTADOS_TOMABLES)
            && (int) $ticket->responsable_id !== (int) $user->id;
    }

    public function comentar(User $user, Ticket $ticket)
    {
        return ! $ticket->estaCerrado() && ($ticket->esDelOrigen($user) || $ticket->esDelDestino($user));
    }

    /** Fotos: el origen agrega al reporte y el destino a la solución, mientras el ticket siga abierto. */
    public function adjuntar(User $user, Ticket $ticket)
    {
        return $this->comentar($user, $ticket);
    }
}
