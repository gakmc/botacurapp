@extends('themes.backoffice.layouts.admin')

@section('title', 'Historial de tickets')

@section('head')
<style>
    .btn-block { width: 100%; }
    .tk-historial .collapsible-body { padding: 16px; background: #fff; }
    .tk-historial .collapsible-body label.tk-etiqueta { display: block; margin-top: 8px; font-size: .85rem; }
    .tk-historial select.browser-default, .tk-historial input[type=date] { height: 40px; }
    .tk-historial .tk-fila { cursor: pointer; }
    .tk-historial .card-content { padding: 14px 16px; }
    .tk-historial .card-content p { margin: 3px 0; }
    .tk-historial i.tiny { vertical-align: middle; }
    .tk-historial .chip { margin: 4px 0 0; height: 26px; line-height: 26px; font-size: 12px; }
    .tk-historial td .chip { margin: 0; }
</style>
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.tickets.index') }}">Tickets</a></li>
<li>Historial</li>
@endsection

@section('dropdown_settings')
@endsection

@php
    $opcion = function ($campo, $valor) use ($filtros) { return (string) ($filtros[$campo] ?? '') === (string) $valor ? 'selected' : ''; };
@endphp

@section('content')
<div class="section tk-historial">
    <div class="row">
        <div class="col s12 m10 offset-m1 l12">

            <div class="row" style="margin-bottom:0;">
                <div class="col s12 m8">
                    <h5 style="margin-top:0;">Historial</h5>
                    <p class="grey-text" style="margin-top:0;">Tickets finalizados y anulados{{ $supervision ? ' de todos los departamentos' : ' de tus departamentos' }}.</p>
                </div>
                <div class="col s12 m4 right-align">
                    <a href="{{ route('backoffice.tickets.index') }}" class="btn-flat waves-effect cyan-text text-darken-2">
                        <i class="material-icons left">arrow_back</i>Bandeja
                    </a>
                </div>
            </div>

            @if ($supervisa)
            <p>
                <label>
                    <input type="checkbox" class="filled-in" id="tk-solo-mi-rol" {{ $supervision ? '' : 'checked' }}>
                    <span>Solo los de mi rol</span>
                </label>
            </p>
            @endif

            {{-- Filtros: abiertos si hay alguno aplicado --}}
            <ul class="collapsible" data-collapsible="accordion">
                <li class="{{ $hayFiltros ? 'active' : '' }}">
                    <div class="collapsible-header {{ $hayFiltros ? 'active' : '' }}">
                        <i class="material-icons">filter_list</i>Filtros
                        @if ($hayFiltros)
                            <span class="new badge cyan darken-2" data-badge-caption="{{ count(array_filter($filtros)) === 1 ? 'aplicado' : 'aplicados' }}">{{ count(array_filter($filtros)) }}</span>
                        @endif
                    </div>
                    <div class="collapsible-body">
                        <form method="GET" action="{{ route('backoffice.tickets.historial') }}">
                            <div class="row" style="margin-bottom:0;">
                                <div class="col s12 l4">
                                    <label class="tk-etiqueta" for="q">Código o título</label>
                                    <input type="text" id="q" name="q" value="{{ $filtros['q'] ?? '' }}" placeholder="Ej: TK-000123 o fuga">
                                </div>
                                <div class="col s6 l2">
                                    <label class="tk-etiqueta" for="desde">Cerrado desde</label>
                                    <input type="date" id="desde" name="desde" class="browser-default" value="{{ $filtros['desde'] ?? '' }}">
                                </div>
                                <div class="col s6 l2">
                                    <label class="tk-etiqueta" for="hasta">Hasta</label>
                                    <input type="date" id="hasta" name="hasta" class="browser-default" value="{{ $filtros['hasta'] ?? '' }}">
                                </div>
                                <div class="col s12 l4">
                                    <label class="tk-etiqueta" for="ubicacion">Ubicación</label>
                                    <input type="text" id="ubicacion" name="ubicacion" value="{{ $filtros['ubicacion'] ?? '' }}" placeholder="Ej: caldera">
                                </div>

                                <div class="col s6 l3">
                                    <label class="tk-etiqueta" for="origen">Origen</label>
                                    <select id="origen" name="origen" class="browser-default">
                                        <option value="">Todos</option>
                                        @foreach ($departamentos as $rol)<option value="{{ $rol->id }}" {{ $opcion('origen', $rol->id) }}>{{ $rol->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col s6 l3">
                                    <label class="tk-etiqueta" for="destino">Destino</label>
                                    <select id="destino" name="destino" class="browser-default">
                                        <option value="">Todos</option>
                                        @foreach ($departamentos as $rol)<option value="{{ $rol->id }}" {{ $opcion('destino', $rol->id) }}>{{ $rol->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col s6 l3">
                                    <label class="tk-etiqueta" for="estado">Estado</label>
                                    <select id="estado" name="estado" class="browser-default">
                                        <option value="">Todos</option>
                                        @foreach (\App\Ticket::ESTADOS_CERRADOS as $estado)<option value="{{ $estado }}" {{ $opcion('estado', $estado) }}>{{ \App\Ticket::etiquetaEstado($estado) }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col s6 l3">
                                    <label class="tk-etiqueta" for="prioridad">Prioridad</label>
                                    <select id="prioridad" name="prioridad" class="browser-default">
                                        <option value="">Todas</option>
                                        @foreach (\App\Ticket::PRIORIDADES as $clave => $etiqueta)<option value="{{ $clave }}" {{ $opcion('prioridad', $clave) }}>{{ $etiqueta }}</option>@endforeach
                                    </select>
                                </div>

                                <div class="col s6 l3">
                                    <label class="tk-etiqueta" for="creador">Creado por</label>
                                    <select id="creador" name="creador" class="browser-default">
                                        <option value="">Cualquiera</option>
                                        @foreach ($creadores as $persona)<option value="{{ $persona->id }}" {{ $opcion('creador', $persona->id) }}>{{ $persona->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col s6 l3">
                                    <label class="tk-etiqueta" for="responsable">Responsable</label>
                                    <select id="responsable" name="responsable" class="browser-default">
                                        <option value="">Cualquiera</option>
                                        @foreach ($responsables as $persona)<option value="{{ $persona->id }}" {{ $opcion('responsable', $persona->id) }}>{{ $persona->name }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col s6 l3" style="margin-top:24px;">
                                    <a href="{{ route('backoffice.tickets.historial') }}" class="btn-flat waves-effect btn-block grey-text text-darken-1">Limpiar</a>
                                </div>
                                <div class="col s6 l3" style="margin-top:24px;">
                                    <button type="submit" class="btn waves-effect waves-light cyan darken-2 btn-block">
                                        <i class="material-icons left">search</i>Filtrar
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </li>
            </ul>

            <p class="grey-text">
                {{ $tickets->total() }} {{ $tickets->total() === 1 ? 'ticket' : 'tickets' }}
                @if ($tickets->total() > $tickets->count()) · página {{ $tickets->currentPage() }} de {{ $tickets->lastPage() }} @endif
            </p>

            @if ($tickets->isEmpty())
                <div class="card-panel center-align grey-text">
                    <i class="material-icons medium">history</i>
                    <p>{{ $hayFiltros ? 'No hay tickets con estos filtros.' : 'Aún no hay tickets finalizados ni anulados.' }}</p>
                </div>
            @else
                {{-- Celular y tablet: tarjetas --}}
                <div class="hide-on-large-only">
                    @foreach ($tickets as $ticket)
                    <a href="{{ route('backoffice.tickets.show', $ticket) }}" class="card black-text" style="display:block;">
                        <div class="card-content">
                            <small class="grey-text">{{ $ticket->codigo }} · cerrado {{ optional($ticket->finalizado_at ?? $ticket->anulado_at)->format('d/m/Y') }}</small>
                            <p style="font-size:1.05rem;">{{ $ticket->titulo }}</p>
                            <p class="grey-text text-darken-1">
                                <i class="material-icons tiny">swap_horiz</i> {{ $ticket->rolOrigen->name }} → {{ $ticket->rolDestino->name }}
                                @if ($ticket->ubicacion) · {{ $ticket->ubicacion }} @endif
                            </p>
                            <div class="chip {{ \App\Ticket::COLORES_ESTADO[$ticket->estado] ?? 'grey' }} white-text">
                                {{ $ticket->estado_etiqueta }}{{ $ticket->finalizado_automatico ? ' (automático)' : '' }}@if ($ticket->responsable) · {{ $ticket->responsable->name }}@endif
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>

                {{-- Escritorio: tabla --}}
                <table class="striped highlight hide-on-med-and-down card-panel" style="padding:0;">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Título</th>
                            <th>Origen → Destino</th>
                            <th>Ubicación</th>
                            <th>Prioridad</th>
                            <th>Estado</th>
                            <th>Responsable</th>
                            <th>Cerrado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tickets as $ticket)
                        <tr class="tk-fila" data-href="{{ route('backoffice.tickets.show', $ticket) }}">
                            <td><a href="{{ route('backoffice.tickets.show', $ticket) }}">{{ $ticket->codigo }}</a></td>
                            <td>{{ $ticket->titulo }}</td>
                            <td>{{ $ticket->rolOrigen->name }} → {{ $ticket->rolDestino->name }}</td>
                            <td>{{ $ticket->ubicacion ?: '—' }}</td>
                            <td>{{ $ticket->prioridad_etiqueta }}</td>
                            <td>
                                <span class="chip {{ \App\Ticket::COLORES_ESTADO[$ticket->estado] ?? 'grey' }} white-text">{{ $ticket->estado_etiqueta }}</span>
                                @if ($ticket->finalizado_automatico)<br><small class="grey-text">automático</small>@endif
                            </td>
                            <td>{{ optional($ticket->responsable)->name ?? '—' }}</td>
                            <td>{{ optional($ticket->finalizado_at ?? $ticket->anulado_at)->format('d/m/Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="center-align">
                    {{ $tickets->links('vendor.pagination.materialize') }}
                </div>
            @endif

        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
(function () {
    var solo = document.getElementById('tk-solo-mi-rol');
    if (solo) solo.addEventListener('change', function () {
        window.location.href = @json(route('backoffice.tickets.historial')) + '?solo_mi_rol=' + (solo.checked ? 1 : 0);
    });
    // Toda la fila de la tabla abre el ticket
    document.querySelectorAll('.tk-fila').forEach(function (fila) {
        fila.addEventListener('click', function (e) {
            if (!e.target.closest('a')) window.location.href = fila.getAttribute('data-href');
        });
    });
})();
</script>
@endsection
