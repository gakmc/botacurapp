@extends('themes.backoffice.layouts.admin')

@section('title', '📆 Gestión de Fechas de Visita')

@section('head')
<link href='{{ asset('assets/fullcalendar/packages/core/main.css') }}' rel='stylesheet' />
<link href='{{ asset('assets/fullcalendar/packages/daygrid/main.css') }}' rel='stylesheet' />
<style>
    .leyenda { display: flex; gap: 20px; margin-bottom: 16px; flex-wrap: wrap; }
    .leyenda-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
    .leyenda-dot { width: 14px; height: 14px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
</style>
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Gestión de Fechas de Visita</strong></p>
    <div class="divider"></div>
    <div class="section">

        <div class="leyenda">
            <div class="leyenda-item">
                <span class="leyenda-dot" style="background:#66BB6A"></span> Regular habilitada
            </div>
            <div class="leyenda-item">
                <span class="leyenda-dot" style="background:#EF5350"></span> Bloqueada
            </div>
            <div class="leyenda-item">
                <span class="leyenda-dot" style="background:#FF9800"></span> Festivo habilitado
            </div>
        </div>

        <div id="calendar"></div>

    </div>
</div>

{{-- Modal gestión de fecha --}}
<div id="modal-fecha" class="modal">
    <div class="modal-content">
        <h5 id="modal-titulo" class="grey-text text-darken-2" style="margin-bottom:16px"></h5>
        <div id="modal-cuerpo"></div>
    </div>
    <div class="modal-footer" id="modal-pie"></div>
</div>
@endsection

@section('foot')
<script src='{{ asset('assets/fullcalendar/packages/core/main.min.js') }}'></script>
<script src='{{ asset('assets/fullcalendar/packages/interaction/main.js') }}'></script>
<script src='{{ asset('assets/fullcalendar/packages/daygrid/main.js') }}'></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {

        if (window.calendarDisponibilidadInit) return;
        window.calendarDisponibilidadInit = true;

        $('#modal-fecha').modal();

        var csrf = '{{ csrf_token() }}';

        var calendarEl = document.getElementById('calendar');

        var calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'es',
            initialView: 'dayGridMonth',
            plugins: ['interaction', 'dayGrid'],
            header: {
                left: 'prev,next today',
                center: 'title',
                right: '',
            },
            buttonText: { today: 'Hoy' },
            height: 650,
            firstDay: 1,
            editable: false,
            eventSources: [{
                url: '{{ route('backoffice.calendario.eventos') }}',
                method: 'GET',
            }],
            dateClick: function (info) {
                var hoy = new Date(); hoy.setHours(0, 0, 0, 0);
                if (info.date < hoy) return;
                var encontrado = calendar.getEvents().find(function (e) {
                    return e.start && e.start.toDateString() === info.date.toDateString();
                });
                abrirModal(info.dateStr, encontrado || null);
            },
            eventClick: function (info) {
                info.jsEvent.preventDefault();
                var fechaStr = info.event.start.toISOString().split('T')[0];
                abrirModal(fechaStr, info.event);
            },
        });

        calendar.render();

        function formatFecha(fechaStr) {
            var partes = fechaStr.split('T')[0].split('-');
            var d = new Date(parseInt(partes[0]), parseInt(partes[1]) - 1, parseInt(partes[2]));
            var s = d.toLocaleDateString('es-CL', {
                weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
            });
            return s.charAt(0).toUpperCase() + s.slice(1);
        }

        function abrirModal(fechaStr, evento) {
            document.getElementById('modal-titulo').textContent = formatFecha(fechaStr);

            var cuerpo = '', pie = '';

            if (evento) {
                var p         = evento.extendedProps;
                var id        = evento.id;
                var estadoClr = p.habilitada ? 'green' : 'red';
                var estadoTxt = p.habilitada ? 'Habilitada' : 'Bloqueada';
                var tipoTxt   = p.tipo === 'festivo' ? 'Festivo' : 'Regular';

                cuerpo += '<p><span class="chip ' + estadoClr + ' white-text">' + estadoTxt + '</span> ';
                cuerpo += '<span class="chip">' + tipoTxt + '</span></p>';
                if (p.nota) cuerpo += '<p><i class="material-icons tiny">notes</i> ' + p.nota + '</p>';

                var toggleTxt = p.habilitada ? '<i class="material-icons left">block</i>Bloquear' : '<i class="material-icons left">check</i>Habilitar';
                var toggleClr = p.habilitada ? 'orange' : 'green';

                pie += '<a class="modal-close btn-flat waves-effect black-text"><i class="material-icons left">close</i>Cerrar</a>';
                pie += '<button onclick="toggleFecha(' + id + ')" class="btn ' + toggleClr + ' waves-effect waves-light">' + toggleTxt + '</button>';
                if (p.tipo === 'festivo') {
                    pie += ' <button onclick="eliminarFecha(' + id + ')" class="btn red waves-effect waves-light">'
                         + '<i class="material-icons left">delete</i>Eliminar</button>';
                }

            } else {
                cuerpo += '<p class="grey-text">Si este día no es jornada regular, puedes agregarlo como festivo habilitado.</p>';
                cuerpo += '<div class="input-field col s12 m6"><input type="text" id="input-nota-nueva">';
                cuerpo += '<label for="input-nota-nueva">Nota (ej: Feriado nacional)</label></div>';

                // cuerpo += '<div class="input-field col s12 m6"><label for="input-tipo">Tipo</label>';
                // cuerpo += '<select id="input-tipo" name="tipo"><option disabled selected>-- Seleccione --</option><option value="regular">Regular</option><option value="festivo">Festivo</option></select>';
                // cuerpo += '</div>';


                cuerpo += '<div class="col s12 m6">';

                cuerpo += '<p>';
                cuerpo += '<label for="festivo"><input class="with-gap" name="tipo" type="radio" id="festivo" /><span class="black-text">Festivo</span></label>';
                cuerpo += '</p>';
 
                cuerpo += '<p>';
                cuerpo += '<label for="regular"><input class="with-gap" name="tipo" type="radio" id="regular" /><span class="black-text">Regular</span></label>';
                cuerpo += '</p>';
                cuerpo += '</div>';

                pie += '<a class="modal-close btn-flat waves-effect black-text"><i class="material-icons left">close</i>Cerrar</a>';
                pie += '<button onclick="agregarFestivo(\'' + fechaStr + '\')" class="btn green waves-effect waves-light">'
                     + '<i class="material-icons left">add</i>Agregar festivo</button>';
            }

            document.getElementById('modal-cuerpo').innerHTML = cuerpo;
            document.getElementById('modal-pie').innerHTML    = pie;
            $('#modal-fecha').modal('open');
        }

        window.toggleFecha = function (id) {
            fetch('/calendario/' + id, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function () { $('#modal-fecha').modal('close'); calendar.refetchEvents(); })
            .catch(function () { M.toast({ html: 'Error al actualizar' }); });
        };

        window.agregarFestivo = function (fecha) {
            var inputNota = document.getElementById('input-nota-nueva');
            var inputTipo = document.getElementsByName('tipo');
            var nota = inputNota ? inputNota.value : '';
            var tipo = inputTipo ? inputTipo.value : 'festivo';
            console.log(tipo);
            fetch('/calendario/festivo', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ tipo: tipo,  fecha: fecha, nota: nota }),
            })
            .then(function (r) {
                if (!r.ok) return r.json().then(function (d) { throw d; });
                return r.json();
            })
            .then(function () { $('#modal-fecha').modal('close'); calendar.refetchEvents(); })
            .catch(function (d) {
                var msg = (d && d.errors) ? Object.values(d.errors).flat().join(' ') : 'Error al agregar';
                Swal.fire({ icon: 'warning', title: msg, toast: true, position: 'top', timer: 3000, showConfirmButton: false });
            });
        };

        window.eliminarFecha = function (id) {
            if (!confirm('¿Eliminar este festivo?')) return;
            fetch('/calendario/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            })
            .then(function (r) { return r.json(); })
            .then(function () { $('#modal-fecha').modal('close'); calendar.refetchEvents(); })
            .catch(function () { Swal.fire({ icon: 'error', title: 'Error al eliminar', toast: true, timer: 3000, showConfirmButton: false }); });
        };

    });
</script>
@endsection
