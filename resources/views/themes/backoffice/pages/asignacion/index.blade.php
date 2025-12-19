@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
<li><a href="{{route('backoffice.reserva.index')}}">Registro de equipos</a></li>
@endsection

@section('head')
<link href='{{ asset('assets/fullcalendar/packages/core/main.css') }}' rel='stylesheet' />
<link href='{{ asset('assets/fullcalendar/packages/daygrid/main.css') }}' rel='stylesheet' />
<link href='{{ asset('assets/fullcalendar/packages/timegrid/main.css') }}' rel='stylesheet' />
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
    @include('themes.backoffice.pages.asignacion.includes.modal_asignacion')
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
document.addEventListener('DOMContentLoaded', function() {
    // Init modal
    if (window.M && M.Modal) {
        var elems = document.querySelectorAll('.modal');
        M.Modal.init(elems);
    }

    if (window.calendarAsignacionInitialized) return;
    window.calendarAsignacionInitialized = true;

    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'es',
        initialView: 'dayGridMonth',
        plugins: ['list', 'interaction', 'dayGrid', 'timeGrid'],
        header: {
            left: 'prev,next,today',
            center: 'title',
            right: 'dayGridMonth,listWeek,timeGridWeek,timeGridDay'
        },
        buttonText: {
            list: 'Lista',
            month: 'Mes',
            week: 'Semana',
            day: 'Día',
            today: 'Hoy'
        },
        height: 500,
        contentHeight: 550,
        firstDay: 1,
        editable: false,

        events: function(fetchInfo, successCallback, failureCallback) {

            const url = "{{ route('backoffice.asignacion.eventos') }}"
                + '?start=' + encodeURIComponent(fetchInfo.startStr)
                + '&end='   + encodeURIComponent(fetchInfo.endStr);

            fetch(url)
                .then(function(response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status);
                    return response.json();
                })
                .then(function(data) {
                    successCallback(data);
                })
                .catch(function(error) {
                    console.error('Error cargando eventos de asignación:', error);
                    failureCallback(error);
                });
        },



        eventClick: function(info) {
            var props = info.event.extendedProps || {};

            // Con equipo → modal
            if (props.asignado) {
                var modalTitle       = document.getElementById('modalTitle');
                var modalDescription = document.getElementById('modalDescription');
                var editButton       = document.querySelector('#asignacionModal .modal-footer .modal-edit');

                if (modalTitle) {
                    var fecha = info.event.start;
                    var fechaStr = fecha ? fecha.toLocaleDateString('es-CL') : '';
                    modalTitle.innerText = 'Equipo asignado - ' + fechaStr;
                }

                if (modalDescription) {
                    if (Array.isArray(props.usuarios)) {
                        modalDescription.innerHTML = props.usuarios
                            .map(function(u){ return '- ' + u; })
                            .join('<br>');
                    } else if (props.usuarios) {
                        modalDescription.textContent = props.usuarios;
                    } else {
                        modalDescription.textContent = 'Sin usuarios asignados.';
                    }
                }

                if (editButton) {
                    if (props.editUrl) {
                        editButton.href = props.editUrl;
                        editButton.style.display = 'inline-block';
                    } else {
                        editButton.style.display = 'none';
                    }
                }

                if (window.M && M.Modal) {
                    var modal = M.Modal.getInstance(document.getElementById('asignacionModal'));
                    modal.open();
                }

                info.jsEvent.preventDefault();
                return;
            }

            // Sin equipo → ir a crear asignación
            if (!props.asignado && props.createUrl) {
                window.location.href = props.createUrl;
                info.jsEvent.preventDefault();
            }
        }
    });

    calendar.render();
});
</script>
@endsection
