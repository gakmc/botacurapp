<?php

namespace App\Services;

use App\Auditoria;
use App\Role;
use App\Ticket;
use App\TicketEvento;
use App\TicketImagen;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

/**
 * Único punto donde se crean tickets y cambian de estado: deja la línea de
 * tiempo (ticket_eventos) y la auditoría (trait Auditable) consistentes.
 */
class TicketService
{
    const MAX_FOTOS = 6;

    /**
     * Crea el ticket con sus fotos del reporte.
     * $datos: titulo, descripcion, prioridad, rol_origen_id, rol_destino_id, ubicacion.
     */
    public function crear(array $datos, array $fotos, User $user)
    {
        $guardadas = [];

        try {
            $ticket = DB::transaction(function () use ($datos, $fotos, $user, &$guardadas) {
                $ticket = Ticket::create($datos + ['creado_por' => $user->id]);

                TicketEvento::create([
                    'ticket_id'    => $ticket->id,
                    'user_id'      => $user->id,
                    'tipo'         => TicketEvento::TIPO_ESTADO,
                    'estado_nuevo' => $ticket->estado,
                ]);

                $this->guardarFotos($ticket, $fotos, TicketImagen::ETAPA_REPORTE, $user, $guardadas);

                return $ticket;
            });
        } catch (\Throwable $e) {
            // La transacción no deshace los archivos ya escritos
            Storage::delete($guardadas);
            throw $e;
        }

        app(TicketNotificacionService::class)->nuevo($ticket, $user);

        return $ticket;
    }

    /**
     * Ejecuta una acción de Ticket::ACCIONES. $user = null solo para el sistema (autocierre).
     * Bloquea la fila y vuelve a validar: si otra persona alcanzó a mover el ticket,
     * la acción se rechaza en vez de pisar su cambio.
     * $extra: rol_destino_id (reenviar), fotos (solucionar: fotos de la solución).
     */
    public function ejecutarAccion(Ticket $ticket, $accion, User $user = null, $comentario = null, array $extra = [])
    {
        $guardadas = [];

        try {
            $ticket = $this->ejecutarAccionEnTransaccion($ticket, $accion, $user, $comentario, $extra, $guardadas);
        } catch (\Throwable $e) {
            // La transacción no deshace los archivos ya escritos
            Storage::delete($guardadas);
            throw $e;
        }

        app(TicketNotificacionService::class)->accion($ticket, $accion, $user);

        return $ticket;
    }

    private function ejecutarAccionEnTransaccion(Ticket $ticket, $accion, $user, $comentario, array $extra, array &$guardadas)
    {
        $definicion = Ticket::ACCIONES[$accion] ?? null;
        if (! $definicion) {
            throw new \DomainException('Acción desconocida.');
        }
        if ($definicion['comentario'] && trim((string) $comentario) === '') {
            throw new \DomainException('Esta acción requiere un comentario.');
        }

        return DB::transaction(function () use ($ticket, $accion, $user, $comentario, $extra, &$guardadas) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticket->id);

            if (! $ticket->admiteAccion($accion)) {
                // También cubre el caso de que otra persona lo haya movido recién
                throw new \DomainException('El ticket ' . $ticket->codigo . ' está ' . $ticket->estado_etiqueta
                    . '; ya no se puede «' . (Ticket::ACCIONES[$accion]['boton'] ?: $accion) . '». Actualiza la página.');
            }
            if ($user) {
                Gate::forUser($user)->authorize('accion', [$ticket, $accion]);
            } elseif (Ticket::ACCIONES[$accion]['lado'] !== Ticket::LADO_SISTEMA) {
                throw new \DomainException('Solo el sistema puede ejecutar esta acción sin usuario.');
            }

            $anterior = $ticket->estado;
            $ticket->aplicarAccion($accion);

            if ($accion === 'recepcionar') {
                $ticket->responsable_id = $user->id;
            }
            if ($accion === 'reenviar') {
                $destino = (int) ($extra['rol_destino_id'] ?? 0);
                if (! $destino || $destino === (int) $ticket->rol_origen_id || ! Role::conTickets()->whereKey($destino)->exists()) {
                    throw new \DomainException('Elige un departamento válido para reenviar el ticket.');
                }
                $ticket->rol_destino_id = $destino;
            }

            $ticket->save();

            TicketEvento::create([
                'ticket_id'       => $ticket->id,
                'user_id'         => optional($user)->id,
                'tipo'            => $accion === 'reenviar' ? TicketEvento::TIPO_REENVIO : TicketEvento::TIPO_ESTADO,
                'rol_id'          => $accion === 'reenviar' ? $ticket->rol_destino_id : null,
                'estado_anterior' => $anterior,
                'estado_nuevo'    => $ticket->estado,
                'comentario'      => trim((string) $comentario) ?: null,
            ]);

            if ($accion === 'solucionar' && ! empty($extra['fotos'])) {
                $this->guardarFotos($ticket, $extra['fotos'], TicketImagen::ETAPA_SOLUCION, $user, $guardadas);
            }

            return $ticket;
        });
    }

    /**
     * Comentario (y/o fotos) en la línea de tiempo. Las fotos del destino quedan
     * como fotos de la solución; las del origen, como parte del reporte.
     */
    public function comentar(Ticket $ticket, User $user, $texto, array $fotos = [])
    {
        $guardadas = [];

        try {
            $evento = DB::transaction(function () use ($ticket, $user, $texto, $fotos, &$guardadas) {
                Gate::forUser($user)->authorize('comentar', $ticket);

                $evento = TicketEvento::create([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $user->id,
                    'tipo'       => TicketEvento::TIPO_COMENTARIO,
                    'comentario' => trim((string) $texto) ?: null,
                ]);

                $etapa = $ticket->esDelDestino($user) && ! $ticket->esDelOrigen($user)
                    ? TicketImagen::ETAPA_SOLUCION : TicketImagen::ETAPA_REPORTE;
                $this->guardarFotos($ticket, $fotos, $etapa, $user, $guardadas);

                // Auditoría: solo que comentó, nunca el texto (cada foto se registra aparte)
                if ($evento->comentario) {
                    app(AuditoriaService::class)->registrar([
                        'categoria'   => Auditoria::CATEGORIA_OPERACIONAL,
                        'evento'      => Auditoria::EVENTO_COMENTO,
                        'auditable'   => $ticket,
                        'descripcion' => 'comentó el ticket ' . $ticket->codigo,
                    ]);
                }

                return $evento;
            });
        } catch (\Throwable $e) {
            Storage::delete($guardadas);
            throw $e;
        }

        // Con el responsable actual (el modelo recibido puede venir de antes de un cambio)
        app(TicketNotificacionService::class)->comentario($ticket->fresh(), $user);

        return $evento;
    }

    /** Otro miembro del destino se hace responsable de un ticket ya recepcionado. */
    public function tomar(Ticket $ticket, User $user)
    {
        return DB::transaction(function () use ($ticket, $user) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticket->id);
            Gate::forUser($user)->authorize('tomar', $ticket);

            $ticket->responsable_id = $user->id;
            $ticket->save();

            TicketEvento::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $user->id,
                'tipo'      => TicketEvento::TIPO_TOMA,
            ]);

            return $ticket;
        });
    }

    /**
     * Guarda las fotos reducidas. $rutas se va llenando a medida que se escriben,
     * para que quien llama pueda borrarlas si algo falla a mitad de camino.
     */
    public function guardarFotos(Ticket $ticket, array $fotos, $etapa, User $user, array &$rutas = [])
    {
        $imagenes = app(ImagenService::class);
        $carpeta = 'tickets/' . now()->format('Y/m');

        foreach (array_slice($fotos, 0, self::MAX_FOTOS) as $foto) {
            $rutas[] = $ruta = $imagenes->guardarReducida($foto, $carpeta);

            TicketImagen::create([
                'ticket_id'  => $ticket->id,
                'ruta'       => $ruta,
                'etapa'      => $etapa,
                'subida_por' => $user->id,
            ]);
        }

        return $rutas;
    }
}
