@extends('themes.backoffice.layouts.admin')

@section('title', 'Registro de Reservas')

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.index')}}">Reservas Mensuales</a></li>
@endsection

@section('head')
<link href='{{ asset('assets/fullcalendar/packages/core/main.css') }}' rel='stylesheet' />
<link href='{{asset('assets/fullcalendar/packages/daygrid/main.css')}}' rel='stylesheet' />
<link href='{{asset('assets/fullcalendar/packages/timegrid/main.css')}}' rel='stylesheet' />
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Reservas Mensuales</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                
                
                <div id="calendar"></div>
                
                
                
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
    <script src='{{ asset('assets/fullcalendar/packages/core/main.min.js')}}'></script>
    <script src='{{ asset('assets/fullcalendar/packages/interaction/main.js')}}'></script>
    <script src='{{ asset('assets/fullcalendar/packages/daygrid/main.js')}}'></script>
    <script src='{{ asset('assets/fullcalendar/packages/timegrid/main.js')}}'></script>
    <script src='{{ asset('assets/fullcalendar/packages/list/main.js')}}'></script>



    <script>
        function convertirFecha(fecha) {
            var parts = fecha.split('-');
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (window.calendarInitialized) return;
            window.calendarInitialized = true;

            var calendarEl = document.getElementById('calendar');
            var eventos = [];

            // Agrupar reservas por fecha y recolectar nombres de clientes
            @php
                $eventosAgrupados = [];

                foreach ($reservasPorMes as $mes => $reservas) {
                    foreach ($reservas as $reserva) {
                        $fecha = $reserva->fecha_visita;
                        if (!isset($eventosAgrupados[$fecha])) {
                            $eventosAgrupados[$fecha] = [
                                'clientes' => [],
                                'reserva' => $reserva // guardamos una para el link y observación
                            ];
                        }
                        $eventosAgrupados[$fecha]['clientes'][] = $reserva->cliente->nombre_cliente;
                    }
                }
            @endphp

            @foreach($eventosAgrupados as $fecha => $data)
                var formatoFecha = convertirFecha('{{ $fecha }}');
                var cantidad = "{{count($data['clientes'])}}";
                
                eventos.push({
                    title: '{{ addslashes(implode(', ', $data["clientes"])) }}',
                    start: formatoFecha + ' '+cantidad+':00',
                    // end: formatoFecha + ' 19:00',
                    url: '{{ route('backoffice.reservas.registro')}}?fecha={{ $fecha }}',
                    description: '{{ addslashes($data["reserva"]->observacion) }}',
                    color: 'primary'

                });
            @endforeach

            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                initialView: 'list',
                plugins: ['list', 'interaction', 'dayGrid', 'timeGrid'],
                header: {
                    left: 'prev,next,today',
                    center: 'title',
                    // right: 'listWeek,dayGridMonth,timeGridWeek,timeGridDay',
                    right: 'dayGridMonth'
                },
                buttonText: {
                    list: 'Lista',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    today: 'Hoy'
                },
                height: 650,
                contentHeight: 800,
                firstDay: 1,
                editable: false,
                eventLimit: false,
                events: eventos,
                eventClick: function (event) {
                    if (event.url) {
                        window.open(event.url, '_blank');
                        return false;
                    }
                },
            });

            calendar.render();
        });
    </script>



    {{-- <script>
        function convertirFecha(fecha) {
            var parts = fecha.split('-');
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (window.calendarInitialized) return;
            window.calendarInitialized = true;

            var calendarEl = document.getElementById('calendar');
            var eventos = [];
            var fechasIncluidas = {};

            @foreach($reservasPorMes as $mes => $reservas)
                @foreach($reservas as $reserva)

                @php
                    $eventosAgrupados = [];
                    $nombres = $reservas->pluck('$reservas->cliente->nombre_cliente')->implode(', ')
                @endphp
                    var formatoFecha = convertirFecha('{{ $reserva->fecha_visita }}');

                    if (!fechasIncluidas[formatoFecha]) {
                        fechasIncluidas[formatoFecha] = true;

                        eventos.push({
                            title: '{{ addslashes($nombres) }}',
                            start: formatoFecha + ' 10:00',
                            end: formatoFecha + ' 19:00',
                            url: '{{ route('backoffice.reserva.show', $reserva->id) }}',
                            description: '{{ addslashes($reserva->observacion) }}',
                            @if (isset($reserva->venta) && $reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                color: 'orange'
                            @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                color: 'green'
                            @else
                                color: 'primary'
                            @endif
                        });
                    }
                @endforeach
            @endforeach

            var calendar = new FullCalendar.Calendar(calendarEl, {
                locale: 'es',
                initialView: 'list',
                plugins: ['list', 'interaction', 'dayGrid', 'timeGrid'],
                header: {
                    left: 'prev,next,today',
                    center: 'title',
                    right: 'listWeek,dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    list: 'Lista',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    today: 'Hoy'
                },
                height: 650,
                contentHeight: 800,
                firstDay: 1,
                editable: false,
                eventLimit: false,
                events: eventos,
                eventColor: 'gradient-45deg-light-blue-cyan',
                eventClick: function (event) {
                    if (event.url) {
                        window.open(event.url, '_blank');
                        return false;
                    }
                },
            });

            calendar.render();
        });
    </script> --}}

    {{-- <script>
        $(document).ready(function () {
            function capitalizarTitulo(titleElement) {
                if (titleElement) {
                    titleElement.textContent = titleElement.textContent.replace(/\b\w/g, function(char) {
                        return char.toUpperCase();
                    });
                }
            }

            let title = document.querySelector('.fc-center h2');
            capitalizarTitulo(title);

            // Observar cambios en el h2
            const observer = new MutationObserver(function (mutationsList, observer) {
                for (const mutation of mutationsList) {
                    if (mutation.type === 'childList' || mutation.type === 'characterData') {
                        capitalizarTitulo(mutation.target.nodeType === 3 ? mutation.target.parentNode : mutation.target);
                    }
                }
            });

            if (title) {
                observer.observe(title, {
                    childList: true,
                    subtree: true,
                    characterData: true
                });
            }
        });
    </script> --}}


@endsection