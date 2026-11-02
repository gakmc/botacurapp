{{--
    Tarjeta de un ticket en la bandeja.
    Parámetros: $ticket, $acciones (['principal' => ?, 'tomar' => bool]), $vista: recibidos | enviados | todos (supervisión).
--}}
@php
    $principal = $acciones['principal'];
    $puedeTomar = $acciones['tomar'];
    $porConfirmar = in_array($ticket->estado, \App\Ticket::ESTADOS_POR_CONFIRMAR);
@endphp
<div class="card tk-ticket">
    <a href="{{ route('backoffice.tickets.show', $ticket) }}" class="card-content black-text" style="display:block;">
        <span class="new badge {{ \App\Ticket::COLORES_PRIORIDAD[$ticket->prioridad] ?? 'grey' }}" data-badge-caption="">{{ $ticket->prioridad_etiqueta }}</span>
        <small class="grey-text">{{ $ticket->codigo }} · {{ $ticket->created_at->locale('es')->diffForHumans() }}</small>

        <span class="card-title">{{ $ticket->titulo }}</span>

        <p class="grey-text text-darken-1">
            <i class="material-icons tiny">swap_horiz</i>
            {{ $ticket->rolOrigen->name }} → {{ $ticket->rolDestino->name }}
            @if ($ticket->imagenes_count > 0)
                &nbsp;<i class="material-icons tiny">photo_camera</i> {{ $ticket->imagenes_count }}
            @endif
        </p>
        @if ($ticket->ubicacion)
            <p class="grey-text text-darken-1"><i class="material-icons tiny">place</i> {{ $ticket->ubicacion }}</p>
        @endif

        <div class="chip {{ \App\Ticket::COLORES_ESTADO[$ticket->estado] ?? 'grey' }} white-text">
            {{ $ticket->estado_etiqueta }}@if ($ticket->responsable) · {{ $ticket->responsable->name }}@endif
        </div>

        {{-- Qué se espera ahora, en palabras --}}
        @if ($vista === 'enviados' && $porConfirmar)
            <p class="orange-text text-darken-3">
                <i class="material-icons tiny">info_outline</i>
                Revisa si quedó bien. Si no lo confirmas, se finalizará solo el {{ $ticket->fecha_autocierre->format('d/m') }}.
            </p>
        @elseif ($vista !== 'recibidos' && $ticket->estado === \App\Ticket::ESTADO_RECHAZADO)
            @php
                $motivo = $ticket->eventos()->where('estado_nuevo', \App\Ticket::ESTADO_RECHAZADO)->latest('id')->value('comentario');
            @endphp
            <p class="red-text text-darken-2">
                <i class="material-icons tiny">block</i>
                {{ $ticket->rolDestino->name }} indicó que no le corresponde{{ $motivo ? ': ' . $motivo : '.' }}
            </p>
        @elseif ($vista !== 'recibidos' && $ticket->estado === \App\Ticket::ESTADO_PENDIENTE)
            <p class="grey-text"><i class="material-icons tiny">hourglass_empty</i> Aún nadie lo toma en {{ $ticket->rolDestino->name }}.</p>
        @elseif ($porConfirmar)
            <p class="grey-text"><i class="material-icons tiny">hourglass_empty</i> Esperando que {{ $ticket->rolOrigen->name }} confirme.</p>
        @endif
    </a>

    <div class="card-action">
        @if ($principal)
            @php
                $colores = ['recepcionar' => 'cyan darken-2', 'comenzar' => 'amber darken-2', 'solucionar' => 'green', 'revisar' => 'teal', 'finalizar' => 'green darken-2'];
                $confirmaciones = [
                    'solucionar' => '¿Marcar como solucionado? ' . $ticket->rolOrigen->name . ' deberá confirmarlo.',
                    'finalizar'  => '¿Finalizar el ticket? Saldrá de la bandeja y quedará en el historial.',
                ];
            @endphp
            <form method="POST" action="{{ route('backoffice.tickets.accion', $ticket) }}" class="tk-form-accion"
                @isset($confirmaciones[$principal]) data-confirmar="{{ $confirmaciones[$principal] }}" @endisset>
                @csrf
                <input type="hidden" name="accion" value="{{ $principal }}">
                <button type="submit" class="btn waves-effect waves-light btn-block {{ $colores[$principal] }}">
                    <i class="material-icons left">{{ \App\Ticket::ICONOS_ACCION[$principal] }}</i>{{ \App\Ticket::ACCIONES[$principal]['boton'] }}
                </button>
            </form>
        @endif
        @if ($puedeTomar)
            <form method="POST" action="{{ route('backoffice.tickets.accion', $ticket) }}" class="tk-form-accion"
                data-confirmar="Quedarás como responsable en lugar de {{ optional($ticket->responsable)->name }}.">
                @csrf
                <input type="hidden" name="accion" value="tomar">
                <button type="submit" class="btn-flat waves-effect btn-block cyan-text text-darken-2" style="margin-top:6px;">
                    <i class="material-icons left">person_add</i>Tomarlo yo
                </button>
            </form>
        @endif
        @if (! $principal && ! $puedeTomar)
            <a href="{{ route('backoffice.tickets.show', $ticket) }}" class="btn-flat waves-effect btn-block cyan-text text-darken-2">
                <i class="material-icons left">chevron_right</i>Ver detalle
            </a>
        @endif
    </div>
</div>
