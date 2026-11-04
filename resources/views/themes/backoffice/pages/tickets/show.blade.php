@extends('themes.backoffice.layouts.admin')

@section('title', $ticket->codigo)

@section('head')
<style>
    .tk-detalle .card-panel { padding: 16px; }
    .tk-detalle h5 { margin: 6px 0 10px; line-height: 1.35; }
    .tk-detalle p { margin: 4px 0; }
    .tk-detalle i.tiny { vertical-align: middle; }
    .tk-detalle .chip { margin: 6px 0; }
    .tk-detalle .tk-foto { margin-bottom: 10px; }
    .tk-detalle .collection .collection-item.avatar { min-height: 64px; }
    .tk-detalle blockquote { margin: 6px 0 0; padding-left: 10px; border-left-color: #0097a7; white-space: pre-line; }
    .tk-detalle .tk-descripcion { white-space: pre-line; margin-top: 10px; }
    .modal.bottom-sheet { max-height: 85%; }
</style>
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.tickets.index') }}">Tickets</a></li>
<li>{{ $ticket->codigo }}</li>
@endsection

@section('dropdown_settings')
@endsection

@php
    $porConfirmar = in_array($ticket->estado, \App\Ticket::ESTADOS_POR_CONFIRMAR);
    $esOrigen = $ticket->esDelOrigen(auth()->user());
    $conModal = ['solucionar', 'rechazar', 'reabrir', 'reenviar', 'anular'];
    $colores = ['recepcionar' => 'cyan darken-2', 'comenzar' => 'amber darken-2', 'solucionar' => 'green', 'revisar' => 'teal', 'finalizar' => 'green darken-2'];
    $confirmaciones = [
        'finalizar' => '¿Finalizar el ticket? Saldrá de la bandeja y quedará en el historial.',
        'revisar'   => 'Quedará En verificación mientras revisas la solución.',
    ];
    $motivoRechazo = $ticket->estado === \App\Ticket::ESTADO_RECHAZADO
        ? optional($ticket->eventos->where('estado_nuevo', \App\Ticket::ESTADO_RECHAZADO)->last())->comentario : null;
@endphp

@section('content')
<div class="section tk-detalle">
    <div class="row">
        <div class="col s12 m10 offset-m1 l8 offset-l2">

            {{-- Cabecera --}}
            <div class="card-panel">
                <span class="new badge {{ \App\Ticket::COLORES_PRIORIDAD[$ticket->prioridad] ?? 'grey' }}" data-badge-caption="">{{ $ticket->prioridad_etiqueta }}</span>
                <small class="grey-text">{{ $ticket->codigo }} · {{ $ticket->created_at->format('d/m/Y H:i') }}</small>

                <h5>{{ $ticket->titulo }}</h5>

                <div class="chip {{ \App\Ticket::COLORES_ESTADO[$ticket->estado] ?? 'grey' }} white-text">
                    {{ $ticket->estado_etiqueta }}@if ($ticket->responsable) · {{ $ticket->responsable->name }}@endif
                </div>

                <p class="grey-text text-darken-1">
                    <i class="material-icons tiny">swap_horiz</i>
                    {{ $ticket->rolOrigen->name }} → {{ $ticket->rolDestino->name }}
                </p>
                @if ($ticket->ubicacion)
                    <p class="grey-text text-darken-1"><i class="material-icons tiny">place</i> {{ $ticket->ubicacion }}</p>
                @endif
                <p class="grey-text text-darken-1"><i class="material-icons tiny">person_outline</i> Creado por {{ optional($ticket->creador)->name ?? '—' }}</p>

                @if ($ticket->descripcion)
                    <p class="tk-descripcion">{{ $ticket->descripcion }}</p>
                @endif

                {{-- Qué se espera ahora --}}
                @if ($porConfirmar)
                    <p class="orange-text text-darken-3" style="margin-top:10px;">
                        <i class="material-icons tiny">info_outline</i>
                        @if ($esOrigen)
                            Revisa si quedó bien. Si no lo confirmas, se finalizará solo el {{ $ticket->fecha_autocierre->format('d/m') }}.
                        @else
                            Esperando que {{ $ticket->rolOrigen->name }} confirme (a más tardar el {{ $ticket->fecha_autocierre->format('d/m') }}).
                        @endif
                    </p>
                @elseif ($ticket->estado === \App\Ticket::ESTADO_RECHAZADO)
                    <p class="red-text text-darken-2" style="margin-top:10px;">
                        <i class="material-icons tiny">block</i>
                        {{ $ticket->rolDestino->name }} indicó que no le corresponde{{ $motivoRechazo ? ': ' . $motivoRechazo : '.' }}
                        @if ($esOrigen) Puedes reenviarlo a otro departamento o anularlo. @endif
                    </p>
                @endif
            </div>

            {{-- Acciones --}}
            @if ($principal || $secundarias || $puedeTomar)
            <div class="card-panel">
                @if ($principal)
                    @if (in_array($principal, $conModal))
                        <button type="button" class="btn btn-large waves-effect waves-light btn-block {{ $colores[$principal] ?? 'cyan darken-2' }} tk-abrir-modal" data-modal="#modal-{{ $principal }}">
                            <i class="material-icons left">{{ \App\Ticket::ICONOS_ACCION[$principal] }}</i>{{ \App\Ticket::ACCIONES[$principal]['boton'] }}
                        </button>
                    @else
                        <form method="POST" action="{{ route('backoffice.tickets.accion', $ticket) }}" class="tk-form-accion"
                            @isset($confirmaciones[$principal]) data-confirmar="{{ $confirmaciones[$principal] }}" @endisset>
                            @csrf
                            <input type="hidden" name="accion" value="{{ $principal }}">
                            <button type="submit" class="btn btn-large waves-effect waves-light btn-block {{ $colores[$principal] ?? 'cyan darken-2' }}">
                                <i class="material-icons left">{{ \App\Ticket::ICONOS_ACCION[$principal] }}</i>{{ \App\Ticket::ACCIONES[$principal]['boton'] }}
                            </button>
                        </form>
                    @endif
                @endif

                {{-- Secundarias: todas llevan texto o confirmación --}}
                @foreach ($secundarias as $accion)
                    @if (in_array($accion, $conModal))
                        <button type="button" class="btn-flat waves-effect btn-block tk-abrir-modal {{ $accion === 'anular' || $accion === 'rechazar' ? 'red-text' : 'cyan-text text-darken-2' }}"
                            data-modal="#modal-{{ $accion }}" style="margin-top:6px;">
                            <i class="material-icons left">{{ \App\Ticket::ICONOS_ACCION[$accion] ?? 'chevron_right' }}</i>{{ \App\Ticket::ACCIONES[$accion]['boton'] }}
                        </button>
                    @else
                        <form method="POST" action="{{ route('backoffice.tickets.accion', $ticket) }}" class="tk-form-accion"
                            data-confirmar="{{ $confirmaciones[$accion] ?? '¿' . \App\Ticket::ACCIONES[$accion]['boton'] . '?' }}">
                            @csrf
                            <input type="hidden" name="accion" value="{{ $accion }}">
                            <button type="submit" class="btn-flat waves-effect btn-block cyan-text text-darken-2" style="margin-top:6px;">
                                <i class="material-icons left">{{ \App\Ticket::ICONOS_ACCION[$accion] ?? 'chevron_right' }}</i>{{ \App\Ticket::ACCIONES[$accion]['boton'] }}
                            </button>
                        </form>
                    @endif
                @endforeach

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
            </div>
            @endif

            {{-- Fotos --}}
            @foreach ([\App\TicketImagen::ETAPA_REPORTE => 'Fotos del reporte', \App\TicketImagen::ETAPA_SOLUCION => 'Fotos de la solución'] as $etapa => $tituloFotos)
                @if (isset($fotos[$etapa]))
                <div class="card-panel">
                    <h6><strong>{{ $tituloFotos }}</strong> <small class="grey-text">({{ $fotos[$etapa]->count() }})</small></h6>
                    <div class="row" style="margin-bottom:0;">
                        @foreach ($fotos[$etapa] as $imagen)
                        <div class="col s4 m3 tk-foto">
                            <img class="materialboxed responsive-img z-depth-1" alt="{{ $tituloFotos }}"
                                src="{{ route('backoffice.tickets.imagen', [$ticket, $imagen]) }}" loading="lazy">
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            @endforeach

            {{-- Línea de tiempo --}}
            <h6 style="margin-top:20px;"><strong>Historial</strong></h6>
            <ul class="collection">
                @foreach ($ticket->eventos as $evento)
                <li class="collection-item avatar">
                    <i class="material-icons circle {{ $evento->tipo === \App\TicketEvento::TIPO_ESTADO ? (\App\Ticket::COLORES_ESTADO[$evento->estado_nuevo] ?? 'grey') : 'blue-grey lighten-1' }}">{{ $evento->icono }}</i>
                    <span class="title">
                        @if ($evento->es_del_sistema)
                            {{ $evento->descripcion }}
                        @else
                            <strong>{{ optional($evento->usuario)->name ?? 'Usuario eliminado' }}</strong> {{ $evento->descripcion }}
                        @endif
                    </span>
                    <p class="grey-text"><small>{{ $evento->created_at->format('d/m/Y H:i') }} · {{ $evento->created_at->locale('es')->diffForHumans() }}</small></p>
                    @if ($evento->comentario)
                        <blockquote>{{ $evento->comentario }}</blockquote>
                    @endif
                </li>
                @endforeach
            </ul>

            {{-- Comentar --}}
            @if ($puedeComentar)
            <div class="card-panel">
                <form method="POST" action="{{ route('backoffice.tickets.comentar', $ticket) }}" enctype="multipart/form-data" class="tk-form-fetch" novalidate>
                    @csrf
                    <div class="input-field">
                        <textarea id="comentario" name="comentario" class="materialize-textarea" maxlength="2000" placeholder="Escribe un comentario"></textarea>
                        <label for="comentario" class="active">Comentario</label>
                        <span class="red-text text-darken-2 tk-error" data-error="comentario"></span>
                    </div>
                    @include('themes.backoffice.pages.tickets.partials.selector-fotos', ['prefijo' => 'com', 'max' => $maxFotos, 'titulo' => 'Adjuntar fotos'])
                    <p class="red-text text-darken-2 tk-error" data-error="general"></p>
                    <button type="submit" class="btn waves-effect waves-light cyan darken-2 btn-block">
                        <i class="material-icons left">send</i>Comentar
                    </button>
                </form>
            </div>
            @endif

        </div>
    </div>
</div>

{{-- Modales de las acciones que piden texto o elegir algo --}}
@foreach (array_intersect($conModal, array_merge([$principal], $secundarias)) as $accion)
<div id="modal-{{ $accion }}" class="modal bottom-sheet">
    <form method="POST" action="{{ route('backoffice.tickets.accion', $ticket) }}" enctype="multipart/form-data" class="tk-form-fetch" novalidate>
        @csrf
        <input type="hidden" name="accion" value="{{ $accion }}">
        <div class="modal-content">
            @if ($accion === 'solucionar')
                <h5>Marcar como solucionado</h5>
                <p class="grey-text">{{ $ticket->rolOrigen->name }} revisará y confirmará que quedó bien.</p>
            @elseif ($accion === 'rechazar')
                <h5>No corresponde a {{ $ticket->rolDestino->name }}</h5>
                <p class="grey-text">El ticket vuelve a {{ $ticket->rolOrigen->name }} para que lo reenvíe o lo anule.</p>
            @elseif ($accion === 'reabrir')
                <h5>Reabrir ticket</h5>
                <p class="grey-text">Vuelve a {{ $ticket->rolDestino->name }} como «En revisión».</p>
            @elseif ($accion === 'reenviar')
                <h5>Reenviar a otro departamento</h5>
                <div class="row" style="margin-bottom:0;">
                    @foreach ($reenvioDestinos as $rol)
                    <div class="col s6 m4">
                        <p><label><input class="with-gap" type="radio" name="rol_destino_id" value="{{ $rol->id }}"><span>{{ $rol->name }}</span></label></p>
                    </div>
                    @endforeach
                </div>
                <span class="red-text text-darken-2 tk-error" data-error="rol_destino_id"></span>
            @elseif ($accion === 'anular')
                <h5>Anular ticket</h5>
                <p class="grey-text">Saldrá de la bandeja y quedará en el historial como «Anulado».</p>
            @endif

            @php
                $obligatorio = \App\Ticket::ACCIONES[$accion]['comentario'];
                $etiqueta = [
                    'solucionar' => 'Nota para ' . $ticket->rolOrigen->name, 'rechazar' => 'Motivo',
                    'reabrir' => '¿Qué falta?', 'reenviar' => 'Comentario', 'anular' => 'Motivo',
                ][$accion];
            @endphp
            <div class="input-field">
                <textarea id="comentario-{{ $accion }}" name="comentario" class="materialize-textarea" maxlength="2000"></textarea>
                <label for="comentario-{{ $accion }}">{{ $etiqueta }} {{ $obligatorio ? '(obligatorio)' : '(opcional)' }}</label>
                <span class="red-text text-darken-2 tk-error" data-error="comentario"></span>
            </div>

            @if ($accion === 'solucionar')
                @include('themes.backoffice.pages.tickets.partials.selector-fotos', ['prefijo' => 'sol', 'max' => $maxFotos, 'titulo' => 'Fotos de la solución'])
            @endif

            <p class="red-text text-darken-2 tk-error" data-error="general"></p>
        </div>
        <div class="modal-footer">
            <a href="#!" class="modal-action modal-close waves-effect btn-flat">Cancelar</a>
            <button type="submit" class="btn waves-effect waves-light {{ in_array($accion, ['anular', 'rechazar']) ? 'red' : 'cyan darken-2' }}">
                {{ \App\Ticket::ACCIONES[$accion]['boton'] }}
            </button>
        </div>
    </form>
</div>
@endforeach
@endsection

@section('foot')
@include('themes.backoffice.pages.tickets.partials.selector-fotos-recursos')
@include('themes.backoffice.pages.tickets.partials.confirmar-acciones')
@include('themes.backoffice.pages.tickets.partials.tiempo-real', ['supervision' => Auth::user()->supervisaTickets()])
<script>
(function () {
    // Tiempo real: si otra persona cambia este ticket, el detalle se recarga solo, salvo
    // que el usuario esté escribiendo, eligiendo fotos o con una ventana abierta (ahí solo avisa)
    var ticketId = @json($ticket->id), pendiente = false;
    function ocupado() {
        var escrito = Array.prototype.some.call(document.querySelectorAll('textarea'), function (t) { return t.value.trim() !== ''; });
        return escrito || document.querySelector('.tk-previa') || document.querySelector('.modal.open')
            || document.querySelector('.swal2-container') || document.querySelector('form button:disabled');
    }
    function refrescar(e) {
        if (document.hidden) { pendiente = true; return; }
        pendiente = false;
        // Reenviado: puede que este departamento ya no tenga acceso; mejor que decida el usuario
        if (e && e.tipo === 'reenviar') return tkAvisarCambio('El ticket fue reenviado a otro departamento');
        ocupado() ? tkAvisarCambio('Hay cambios en este ticket') : location.reload();
    }
    tkAlCambiarTicket(function (e) {
        if (e && Number(e.ticketId) !== Number(ticketId)) return;
        refrescar(e);
    });
    document.addEventListener('visibilitychange', function () { if (pendiente && !document.hidden) refrescar(null); });

    // Modales (Materialize 0.100: plugin de jQuery)
    $('.modal').modal();
    document.querySelectorAll('.tk-abrir-modal').forEach(function (btn) {
        btn.addEventListener('click', function () { $(btn.getAttribute('data-modal')).modal('open'); });
    });

    // Formularios con texto y fotos: se envían por fetch; si sale bien se recarga el detalle
    var sinFotos = { cantidad: function () { return 0; }, reducidas: function () { return Promise.resolve([]); } };
    document.querySelectorAll('.tk-form-fetch').forEach(function (form) {
        var contenedor = form.querySelector('.tk-selector-fotos');
        var selector = contenedor ? TkFotos.iniciar(contenedor) : sinFotos;
        var boton = form.querySelector('button[type=submit]'), textoBoton = boton.innerHTML, enviando = false;

        function error(campo, texto) {
            var clave = campo.indexOf('fotos') === 0 ? 'fotos' : campo;
            var el = form.querySelector('.tk-error[data-error="' + clave + '"]') || form.querySelector('.tk-error[data-error=general]');
            if (el && !el.textContent) el.textContent = texto;
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (enviando) return;
            enviando = true; boton.disabled = true; boton.innerHTML = 'Enviando…';
            form.querySelectorAll('.tk-error').forEach(function (el) { el.textContent = ''; });

            TkFotos.enviar(form, selector).then(function (r) {
                if (r.status === 200 && r.json.ok) { window.location.reload(); return; }
                if (r.json.errors) {
                    Object.keys(r.json.errors).forEach(function (campo) { error(campo, r.json.errors[campo][0]); });
                } else if (r.status === 419) {
                    error('general', 'La sesión expiró. Recarga la página e inténtalo de nuevo.');
                } else {
                    error('general', r.json.mensaje || 'No se pudo guardar. Inténtalo de nuevo.');
                }
                throw null;
            }).catch(function (err) {
                if (err) error('general', 'Sin conexión. Revisa tu internet e inténtalo de nuevo.');
                enviando = false; boton.disabled = false; boton.innerHTML = textoBoton;
            });
        });
    });
})();
</script>
@endsection
