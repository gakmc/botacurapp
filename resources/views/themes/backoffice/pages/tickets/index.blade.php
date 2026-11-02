@extends('themes.backoffice.layouts.admin')

@section('title', 'Tickets')

@section('head')
<style>
    /* En Materialize 0.100.2 btn-block solo hace display:block */
    .btn-block { width: 100%; }
    .tk-pestanas .btn, .tk-pestanas .btn-flat { padding: 0 8px; }
    /* Contador dentro del botón de la pestaña (el badge de Materialize flota fuera) */
    .tk-num { display: inline-block; min-width: 20px; height: 20px; line-height: 20px; padding: 0 6px; margin-left: 6px; border-radius: 10px; font-size: 12px; font-weight: 600; text-align: center; }
    .tk-filtros { margin: 10px 0; }
    .tk-filtros .chip { cursor: pointer; }
    .tk-ticket .card-content { padding: 16px; }
    .tk-ticket .card-title { font-size: 1.2rem; line-height: 1.35; margin: 6px 0; }
    .tk-ticket .card-content p { margin: 4px 0; }
    .tk-ticket .card-action { padding: 12px 16px; }
    .tk-ticket i.tiny { vertical-align: middle; }
    .tk-ticket .chip { margin: 6px 0 0; }
    /* Espacio para que el botón flotante (+) no tape la acción de la última tarjeta */
    .tk-lista { padding-bottom: 80px; }
    /* Celular y tablet: una columna a la vez (pestañas); escritorio: las dos lado a lado */
    @media only screen and (max-width: 992px) {
        .tk-columna:not(.activa) { display: none; }
    }
</style>
@endsection

@section('breadcrumbs')
<li>Tickets</li>
@endsection

@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <div class="row">
        <div class="col s12 {{ $supervision ? 'm10 offset-m1 l8 offset-l2' : 'm10 offset-m1 l12' }} tk-lista">

            <a href="{{ route('backoffice.tickets.historial') }}" class="btn-flat waves-effect cyan-text text-darken-2 right">
                <i class="material-icons left">history</i>Historial
            </a>
            {{-- Solo si este dispositivo aún no recibe avisos (lo muestra el JS) --}}
            <a href="#!" id="tk-activar-push" class="btn-flat waves-effect orange-text text-darken-3 right hide">
                <i class="material-icons left">notifications_active</i>Activar avisos
            </a>

            {{-- Administrador: todos los departamentos o solo los de su rol (se recuerda en la sesión) --}}
            @if ($supervisa)
            <p>
                <label>
                    <input type="checkbox" class="filled-in" id="tk-solo-mi-rol" {{ $supervision ? '' : 'checked' }}>
                    <span>Solo los de mi rol</span>
                </label>
            </p>
            @endif
            <div style="clear:both;"></div>

            @if ($supervision)

                <div class="row" style="margin-bottom:0;">
                    <div class="col s12 m6" style="padding-left:0;">
                        <select class="browser-default" id="tk-departamento" aria-label="Departamento">
                            <option value="">Todos los departamentos</option>
                            @foreach ($departamentos as $rol)
                                <option value="{{ $rol->id }}" {{ $departamento == $rol->id ? 'selected' : '' }}>{{ $rol->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="tk-filtros">
                    @foreach ($filtros as $clave => $nombre)
                        <a href="{{ route('backoffice.tickets.index', ['filtro' => $clave, 'departamento' => $departamento]) }}"
                            class="chip {{ $filtro === $clave ? 'cyan darken-2 white-text' : '' }}">{{ $nombre }}</a>
                    @endforeach
                </div>

                <h6 class="grey-text text-darken-1">
                    {{ $departamento ? 'Tickets de ' . optional($departamentos->firstWhere('id', $departamento))->name : 'Todos los departamentos' }}
                    · {{ $todos->count() }} {{ $todos->count() === 1 ? 'activo' : 'activos' }}
                </h6>

                @forelse ($todos as $ticket)
                    @include('themes.backoffice.pages.tickets.partials.tarjeta', ['acciones' => $acciones[$ticket->id], 'vista' => 'todos'])
                @empty
                    <div class="card-panel center-align grey-text">
                        <i class="material-icons medium">inbox</i>
                        <p>{{ $filtro !== 'todos' ? 'No hay tickets con este filtro.' : 'No hay tickets activos.' }}</p>
                    </div>
                @endforelse

            @else

                {{-- Pestañas (solo celular/tablet); el contador muestra lo que espera una acción de este lado --}}
                <div class="row tk-pestanas hide-on-large-only">
                    @foreach (['recibidos' => 'Recibidos', 'enviados' => 'Enviados'] as $clave => $nombre)
                    <div class="col s6">
                        <a href="{{ route('backoffice.tickets.index', ['bandeja' => $clave, 'filtro' => $filtro]) }}" data-bandeja="{{ $clave }}"
                            class="tk-pestana {{ $bandeja === $clave ? 'btn cyan darken-2' : 'btn-flat cyan-text text-darken-2' }} btn-block waves-effect">
                            {{ $nombre }}
                            @if ($contadores[$clave] > 0)
                                <span class="tk-num {{ $bandeja === $clave ? 'white cyan-text text-darken-2' : 'red white-text' }}">{{ $contadores[$clave] }}</span>
                            @endif
                        </a>
                    </div>
                    @endforeach
                </div>

                <div class="tk-filtros">
                    @foreach ($filtros as $clave => $nombre)
                        <a href="{{ route('backoffice.tickets.index', ['bandeja' => $bandeja, 'filtro' => $clave]) }}"
                            class="chip tk-filtro {{ $filtro === $clave ? 'cyan darken-2 white-text' : '' }}">{{ $nombre }}</a>
                    @endforeach
                </div>

                <div class="row">
                    @foreach (['recibidos' => ['Recibidos', $recibidos], 'enviados' => ['Enviados', $enviados]] as $clave => list($nombre, $lista))
                    <div class="col s12 l6 tk-columna {{ $bandeja === $clave ? 'activa' : '' }}" data-bandeja="{{ $clave }}">
                        {{-- Título de columna (solo escritorio) --}}
                        <h5 class="hide-on-med-and-down">
                            {{ $nombre }}
                            @if ($contadores[$clave] > 0)
                                <span class="tk-num red white-text">{{ $contadores[$clave] }}</span>
                            @endif
                        </h5>

                        @forelse ($lista as $ticket)
                            @include('themes.backoffice.pages.tickets.partials.tarjeta', ['acciones' => $acciones[$ticket->id], 'vista' => $clave])
                        @empty
                            <div class="card-panel center-align grey-text">
                                <i class="material-icons medium">{{ $clave === 'recibidos' ? 'inbox' : 'outbox' }}</i>
                                <p>
                                    @if ($filtro !== 'todos')
                                        No hay tickets con este filtro.
                                    @elseif ($clave === 'recibidos')
                                        Tu departamento no tiene tickets pendientes.
                                    @else
                                        No tienes tickets enviados en curso.
                                    @endif
                                </p>
                            </div>
                        @endforelse
                    </div>
                    @endforeach
                </div>

            @endif

        </div>
    </div>

    @can('create', App\Ticket::class)
    <div class="fixed-action-btn">
        <a href="{{ route('backoffice.tickets.create') }}" class="btn-floating btn-large waves-effect waves-light red" title="Nuevo ticket">
            <i class="large material-icons">add</i>
        </a>
    </div>
    @endcan
</div>
@endsection

@section('foot')
@include('themes.backoffice.pages.tickets.partials.confirmar-acciones')
<script>
(function () {
    var base = @json(route('backoffice.tickets.index'));

    // Avisos push: activarNotificacionesPush() se define en el layout después de este script
    // (por eso se espera a "load"); el botón aparece solo si el dispositivo no está suscrito
    window.addEventListener('load', function () {
        var activar = document.getElementById('tk-activar-push');
        if (!activar || !('serviceWorker' in navigator) || !('PushManager' in window) || typeof activarNotificacionesPush !== 'function') return;

        navigator.serviceWorker.getRegistration('/sw.js')
            .then(function (reg) { return reg ? reg.pushManager.getSubscription() : null; })
            .then(function (sub) { if (!sub || Notification.permission !== 'granted') activar.classList.remove('hide'); })
            .catch(function () { activar.classList.remove('hide'); });
        activar.addEventListener('click', function (e) {
            e.preventDefault();
            Promise.resolve(activarNotificacionesPush()).then(function () {
                if (Notification.permission === 'granted') activar.classList.add('hide');
            });
        });
    });

    // Administrador: cambiar entre todos los departamentos y solo los de su rol
    var solo = document.getElementById('tk-solo-mi-rol');
    if (solo) solo.addEventListener('change', function () { window.location.href = base + '?solo_mi_rol=' + (solo.checked ? 1 : 0); });

    var departamento = document.getElementById('tk-departamento');
    if (departamento) departamento.addEventListener('change', function () {
        var url = new URL(window.location.href);
        departamento.value ? url.searchParams.set('departamento', departamento.value) : url.searchParams.delete('departamento');
        window.location.href = url.toString();
    });

    // Pestañas en celular: cambian de columna sin recargar y dejan la elegida en la URL
    // (así "volver" desde una acción regresa a la misma pestaña)
    document.querySelectorAll('.tk-pestana').forEach(function (pestana) {
        pestana.addEventListener('click', function (e) {
            e.preventDefault();
            var elegida = pestana.getAttribute('data-bandeja');
            document.querySelectorAll('.tk-columna').forEach(function (col) {
                col.classList.toggle('activa', col.getAttribute('data-bandeja') === elegida);
            });
            document.querySelectorAll('.tk-pestana').forEach(function (p) {
                var activa = p === pestana, num = p.querySelector('.tk-num');
                p.className = 'tk-pestana btn-block waves-effect ' + (activa ? 'btn cyan darken-2' : 'btn-flat cyan-text text-darken-2');
                if (num) num.className = 'tk-num ' + (activa ? 'white cyan-text text-darken-2' : 'red white-text');
            });
            var url = new URL(window.location.href);
            url.searchParams.set('bandeja', elegida);
            history.replaceState(null, '', url.toString());
            document.querySelectorAll('.tk-filtro').forEach(function (chip) {
                var u = new URL(chip.href); u.searchParams.set('bandeja', elegida); chip.href = u.toString();
            });
        });
    });
})();
</script>
@endsection
