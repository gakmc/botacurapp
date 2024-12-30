@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.index')}}">Todas las Reservas</a></li>
@endsection

@section('head')
<link href='{{ asset('assets/fullcalendar/packages/core/main.css') }}' rel='stylesheet' />
<link href='{{asset('assets/fullcalendar/packages/daygrid/main.css')}}' rel='stylesheet' />
<link href='{{asset('assets/fullcalendar/packages/timegrid/main.css')}}' rel='stylesheet' />
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Reservaciones</strong></p>
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

function convertirHora(hora) {
        var [time, modifier] = hora.split(' ');
        let [hours, minutes] = time.split(':');

        if (hours === '12') {
        hours = '12';
    }

    if (modifier === 'PM') {
        hours = parseInt(hours, 10) + 12;
    }

    return `${hours}:${minutes}`;
}


document.addEventListener('DOMContentLoaded', function() {

    if (window.calendarInitialized) {

        return;
    }
    window.calendarInitialized = true;


    var calendarEl = document.getElementById('calendar');

    var eventos = [];

    @foreach($reservasPorMes as $mes => $reservas)
        @foreach($reservas as $reserva)
        var formatoFecha = convertirFecha('{{ $reserva->fecha_visita }}');

        @php
            $saunaHorarios = [];
            $tinajaHorarios = [];
        @endphp

        @foreach ($reserva->visitas as $visita)
            // Evitar horarios sauna duplicados
            @if ($visita->horario_sauna && !in_array($visita->horario_sauna, $saunaHorarios))
            var horaSauna = convertirHora('{{ $visita->horario_sauna }}');

            eventos.push({
                title: 'Sauna - {{ addslashes($reserva->cliente->nombre_cliente) }} - {{ $reserva->cantidad_personas }} personas - {{$reserva->programa->nombre_programa}}',
                start: formatoFecha + ' {{ $visita->horario_sauna }}',
                end: formatoFecha + ' {{ $visita->hora_fin_sauna }}',
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
            @php $saunaHorarios[] = $visita->horario_sauna; @endphp
            @endif

            // Evitar horarios tinaja duplicados
            @if ($visita->horario_tinaja && !in_array($visita->horario_tinaja, $tinajaHorarios))
            var horaTinaja = convertirHora('{{ $visita->horario_tinaja }}');

            eventos.push({
                title: 'Tinaja - {{ addslashes($reserva->cliente->nombre_cliente) }} - {{ $reserva->cantidad_personas }} personas - {{$reserva->programa->nombre_programa}}',
                start: formatoFecha + ' ' + horaTinaja,
                end: formatoFecha + ' {{ $visita->hora_fin_tinaja }}',
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
            @php $tinajaHorarios[] = $visita->horario_tinaja; @endphp
            @endif

            // Masajes siempre se agregan
            @if ($visita->horario_masaje)
            var horaMasaje = convertirHora('{{ $visita->horario_masaje }}');

            eventos.push({
                title: 'Masaje - {{ addslashes($reserva->cliente->nombre_cliente) }} - {{ $reserva->cantidad_personas }} personas - {{$reserva->programa->nombre_programa}}',
                start: formatoFecha + ' ' + horaMasaje,
                end: formatoFecha + ' {{ $visita->hora_fin_masaje_extra }}',
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
            @endif
        @endforeach

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
        buttonText:{
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
        eventClick: function(event) {
            if (event.url) {
                window.open(event.url, '_blank');
                return false;
            }
        },
    });

    
    calendar.render();
    
    let title = document.querySelector('.fc-center h2');
    if (title) {
                title.textContent = title.textContent.replace(/\b\w/g, function(char) {
                    return char.toUpperCase();
                });
            }
});
</script>
@endsection