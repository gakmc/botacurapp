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
    document.addEventListener('DOMContentLoaded', function () {

        if (window.calendarInitialized) return;
            window.calendarInitialized = true;
            
        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'es',
            initialView: 'dayGridMonth',
            plugins: ['list', 'interaction', 'dayGrid', 'timeGrid'],
            header: {
                left: 'prev,next,today',
                center: 'title',
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
            firstDay: 1,
            editable: false,
            eventSources: [{
                url: '{{ route('backoffice.reservas.eventos') }}',
                method: 'GET',
            }],
            // eventClick: function (info) {
            //     if (info.event.url) {
            //         window.open(info.event.url, '');
            //         info.jsEvent.preventDefault();
            //     }
            // },
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

            // Agrupar reservas por fecha y recolectar nombres de clientes
            @php
                $eventosAgrupados = [];

                foreach ($reservasPorMes as $mes => $reservas) {
                    foreach ($reservas as $reserva) {
                        $fecha = $reserva->fecha_visita;
                        if (!isset($eventosAgrupados[$fecha])) {
                            $eventosAgrupados[$fecha] = [
                                'clientes' => [],
                                'reservas' => [],
                                'reserva' => $reserva // guardamos una para el link y observación
                            ];
                        }
                        $eventosAgrupados[$fecha]['clientes'][] = $reserva->cliente->nombre_cliente;
                        $eventosAgrupados[$fecha]['reservas'][] = $reserva;
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
    </script> --}}



<script>
    $(document).ready(function () {
        
  
        @if(session('info'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'info',
                title: '{{ session('info') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

        @if(session('success'))
            Swal.fire({
                toast: true,
                position: '',
                icon: 'success',
                title: '{{ session('success') }}',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                  didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                  }
            });
        @endif

    });
        

</script>

@endsection