<?php

namespace App\Services;

use App\Ticket;
use App\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Notificaciones push de los tickets (WebPushService). Nunca avisa a quien hizo la
 * acción, y un fallo del push no interrumpe la acción: solo queda en el log.
 */
class TicketNotificacionService
{
    /** Ticket nuevo o reenviado: todo el departamento de destino. */
    public function nuevo(Ticket $ticket, User $autor = null)
    {
        $titulo = ($ticket->prioridad === 'urgente' ? '🔴 Urgente · ' : '') . 'Nuevo ticket ' . $ticket->codigo;

        $this->enviar($this->usuariosDelRol($ticket->rol_destino_id), $autor, $titulo,
            $ticket->rolOrigen->name . ': ' . $ticket->titulo, $ticket);
    }

    /** Después de una acción de Ticket::ACCIONES (o 'tomar'). */
    public function accion(Ticket $ticket, $accion, User $autor = null)
    {
        switch ($accion) {
            case 'reenviar':
                $this->nuevo($ticket, $autor);
                return;

            case 'solucionar':
                $this->enviar($this->origen($ticket), $autor, 'Ticket ' . $ticket->codigo . ' solucionado',
                    $ticket->rolDestino->name . ' lo marcó como solucionado. Revisa si quedó bien.', $ticket);
                return;

            case 'rechazar':
                $this->enviar($this->origen($ticket), $autor, 'Ticket ' . $ticket->codigo . ' rechazado',
                    $ticket->rolDestino->name . ' indicó que no le corresponde.', $ticket);
                return;

            case 'reabrir':
                $this->enviar($this->destino($ticket), $autor, 'Ticket ' . $ticket->codigo . ' reabierto',
                    $ticket->rolOrigen->name . ' indicó que aún falta: ' . $ticket->titulo, $ticket);
                return;

            case 'anular':
                $this->enviar($this->destino($ticket)->merge($this->origen($ticket)), $autor, 'Ticket ' . $ticket->codigo . ' anulado',
                    $ticket->titulo, $ticket);
                return;
        }
    }

    /** Comentario: avisa al otro lado del ticket. */
    public function comentario(Ticket $ticket, User $autor)
    {
        $destinatarios = $ticket->esDelDestino($autor) && ! $ticket->esDelOrigen($autor)
            ? $this->origen($ticket)
            : $this->destino($ticket);

        $this->enviar($destinatarios, $autor, 'Comentario en ' . $ticket->codigo,
            $autor->name . ' comentó: ' . $ticket->titulo, $ticket);
    }

    /** Recordatorio al origen: mañana se finaliza solo. */
    public function recordatorio(Ticket $ticket)
    {
        $this->enviar($this->origen($ticket), null, 'Confirma el ticket ' . $ticket->codigo,
            'Si no lo revisas, se finalizará automáticamente el ' . $ticket->fecha_autocierre->format('d/m') . '.', $ticket);
    }

    // DESTINATARIOS

    /** Quien creó el ticket o, si ya no existe, su departamento. */
    protected function origen(Ticket $ticket)
    {
        return $ticket->creador ? collect([$ticket->creador]) : $this->usuariosDelRol($ticket->rol_origen_id);
    }

    /** El responsable o, si nadie lo ha tomado, todo el departamento de destino. */
    protected function destino(Ticket $ticket)
    {
        return $ticket->responsable ? collect([$ticket->responsable]) : $this->usuariosDelRol($ticket->rol_destino_id);
    }

    protected function usuariosDelRol($rolId)
    {
        return User::whereHas('roles', function ($q) use ($rolId) {
            $q->where('roles.id', $rolId);
        })->get();
    }

    protected function enviar($usuarios, $autor, $titulo, $cuerpo, Ticket $ticket)
    {
        $usuarios = collect($usuarios)->filter()->unique('id')->reject(function ($u) use ($autor) {
            return $autor && (int) $u->id === (int) $autor->id;
        })->values();

        if ($usuarios->isEmpty()) {
            return;
        }

        try {
            app(WebPushService::class)->sendToUsers($usuarios, [
                'title' => $titulo,
                'body'  => $cuerpo,
                'url'   => route('backoffice.tickets.show', $ticket),
            ]);
        } catch (Throwable $e) {
            Log::warning('Push de ticket ' . $ticket->codigo . ' no enviado: ' . $e->getMessage());
        }
    }
}
