@extends('themes.backoffice.layouts.admin')

@section('title', 'Asistencia')

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.index')}}">Registro de equipos</a></li>
@endsection

@section('head')
<link href='{{ asset('assets/fullcalendar/packages/core/main.css') }}' rel='stylesheet' />
<link href='{{asset('assets/fullcalendar/packages/daygrid/main.css')}}' rel='stylesheet' />
<link href='{{asset('assets/fullcalendar/packages/timegrid/main.css')}}' rel='stylesheet' />
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Asignacion de equipos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
    

                    <div id="calendar"></div>



            </div>
        </div>
    </div>
    @include('themes.backoffice.pages.asistencia.includes.modal_asistencia')
</div>
@endsection

@section('foot')
<script src='{{ asset('assets/fullcalendar/packages/core/main.min.js')}}'></script>
<script src='{{ asset('assets/fullcalendar/packages/interaction/main.js')}}'></script>
<script src='{{ asset('assets/fullcalendar/packages/daygrid/main.js')}}'></script>
<script src='{{ asset('assets/fullcalendar/packages/timegrid/main.js')}}'></script>
<script src='{{ asset('assets/fullcalendar/packages/list/main.js')}}'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

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
        // Inicializa el modal de Materialize
        var elems = document.querySelectorAll('.modal');
        var instances = M.Modal.init(elems);

        if (window.calendarInitialized) {
            return;
        }
        window.calendarInitialized = true;

        var calendarEl = document.getElementById('calendar');
        var eventos = [];
        var usuarios = '';
        var fecha = '';

        

@foreach ($fechas as $fecha)
    @php
        $asistencia = $asignados->get($fecha);
        $usuariosRoles = collect(); // aseguramos que siempre exista

        if ($asistencia) {
            $usuariosRoles = $asistencia->users->map(function ($user) {
                $roles = $user->roles->pluck('name')->implode(', ');
                return $user->name . ' (' . $roles . ')';
            });
        }

        $nombresUsuarios = json_encode($usuariosRoles->all());
        $fechaFormateada = \Carbon\Carbon::parse($fecha)->format('d-m-Y');
    @endphp

    @if ($asistencia)
        eventos.push({
            title: `Equipo asistió - {{ $usuariosRoles->implode('; ') }}`,
            start: '{{ $fecha }}',
            color: 'primary',
            description: {!! $nombresUsuarios !!},
            modalData: {
                title: 'Asistencia - {{ $fechaFormateada }}',
                description: {!! $nombresUsuarios !!},
                editUrl: '{{ route('backoffice.asistencia.edit', $asistencia->id) }}'
            }
        });
    @else
        eventos.push({
            title: 'No se registró asistencia',
            start: '{{ $fecha }}',
            color: 'red',
            url: '{{ route('backoffice.asistencia.create', ['fecha' => $fecha]) }}'
        });
    @endif
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
            eventClick: function(info) {
                // Si el evento tiene una URL y no tiene modalData, es un día sin asignación → redirigir
                if (!info.event.extendedProps.modalData && info.event.url) {
                    window.location.href = info.event.url;
                    return;
                }

                // Obtener la información del evento clicado
                let modalData = info.event.extendedProps.modalData;

                // Configurar el contenido del modal
                document.getElementById('modalTitle').innerText = modalData.title;

                // Si la descripción es un array (nombres de usuarios), mapear y mostrar
                let modalDescription = '';
                if (Array.isArray(modalData.description)) {
                    modalDescription = modalData.description.map(name => `- ${name}`).join('<br>');
                } else {
                    modalDescription = modalData.description;
                }

                document.getElementById('modalDescription').innerHTML = modalDescription;

                let editButton = document.querySelector('#asistenciaModal .modal-footer .modal-edit');

                if (modalData.editUrl) {
                    editButton.href = modalData.editUrl;
                    editButton.style.display = 'inline-block';
                } else {
                    editButton.style.display = 'none';
                }

                // Mostrar el modal
                let modal = M.Modal.getInstance(document.getElementById('asistenciaModal'));
                modal.open();

                info.jsEvent.preventDefault();
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