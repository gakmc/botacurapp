@extends('themes.backoffice.layouts.admin')

@section('title', 'Masajes')

@section('breadcrumbs')
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
<div class="row">
    <div class="col s12 m4 l3 left">
        <p class="caption"><strong>Masajes desde <a href="?page=1">{{ now()->format('d-m-Y') }}</a></strong></p>
    </div>
        @if(isset($contador) && Auth::user()->has_role(config('app.masoterapeuta_role')))
        
        <div class="col s12 m4 l3 right">
            <ul class="collection">
                <li class="collection-item avatar">
                    <i class="material-icons circle red">person</i>
                    <span class="title">Cantidad</span>
                    <p>Total:</p>
                    {{-- <a href="#!" class="secondary-content"><i class="material-icons">group_add</i></a> --}}
                    <span class="secondary-content" style="color: #039B7B">
                        {{$contador}} {{$contador > 1 ? "Personas" : "Persona"}}
                    </span>
                </li>
            </ul>
    
        </div>
    
    
        @endif
    </div>

    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">

<div class="col s12">


        <div class="card-panel">

            
    @if(Auth::user()->has_role(config('app.masoterapeuta_role')))
    <form method="POST" action="{{ route('backoffice.masaje.asignar_multiples') }}">
        @csrf
    @endif

            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Horario Masajes</th>
                        <th>Nombre Cliente</th>
                        <th>Cantidad Personas</th>
                        <th>Tipo Masajes</th>
                        <th>Lugar Masajes</th>
                        <th>Estado</th>
                        <th>Asignación</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($reservasPaginadas as $fecha => $reservasPorFecha)
                        <h5>
                            @if (now()->format('d-m-Y') == $fecha) <strong>Hoy</strong> @endif
                            {{$fecha}}
                        </h5>

                        @php
                            // 1) Junta todos los masajes del día
                            $masajesDelDia = collect();
                        @endphp

                        @foreach ($reservasPorFecha as $reserva)
                            @if (!isset($distribucionHorarios[$reserva->id]))
                                <p>No hay horarios disponibles para esta visita.</p>
                            @endif
                            @php $masajesDelDia = $masajesDelDia->merge($reserva->masajes); @endphp
                        @endforeach

                        @php
                            // 2) Orden global por hora y persona (NULL al final)
                            $masajesDelDia = $masajesDelDia->sortBy(function($m){
                                $h = $m->horario_masaje ? substr($m->horario_masaje,0,5) : '99:99';
                                return $h.'-'.str_pad($m->persona,2,'0',STR_PAD_LEFT);
                            })->values();
                        @endphp

                        @foreach ($masajesDelDia as $masaje)
                            <tr>
                                @if(Auth::user()->has_role(config('app.masoterapeuta_role')))
                                    {{-- (Sugerencia: abre el <form> una sola vez fuera de la tabla) --}}
                                @endif

                                <td class="{{ $masaje->tiempo_extra ? 'blue-text' : '' }}">
                                    {{ substr($masaje->horario_masaje,0,5) }} -
                                    @if ($masaje->hora_fin_masaje)
                                        {{ substr($masaje->hora_fin_masaje,0,5) }}
                                    @else
                                        {{ substr($masaje->hora_fin_masaje_extra,0,5) }}
                                    @endif
                                </td>

                                <td>{{ optional($masaje->reserva->cliente)->nombre_cliente }}</td>
                                <td>Persona {{ $masaje->persona }}</td>
                                <td>{{ $masaje->tipo_masaje }}</td>
                                <td>{{ optional($masaje->lugarMasaje)->nombre }}</td>

                                <td>
                                    <span class="estado badge white-text cyan"
                                        data-fecha="{{ $masaje->reserva->fecha_visita }}"
                                        data-inicio="{{ substr($masaje->horario_masaje,0,5) }}"
                                        @if ($masaje->hora_fin_masaje)
                                            data-fin="{{ substr($masaje->hora_fin_masaje,0,5) }}"
                                        @else
                                            data-fin="{{ substr($masaje->hora_fin_masaje_extra,0,5) }}"
                                        @endif
                                    >Pendiente</span>
                                </td>

                                <td>
                                    @php $masajeAsignado = $masaje; @endphp
                                    @if ($masajeAsignado->user)
                                        <strong style="color:#039B7B;">{{ $masajeAsignado->user->name }}</strong>
                                    @else
                                        @if(Auth::user()->has_role(config('app.masoterapeuta_role')) && $fecha == now()->format('d-m-Y'))
                                            <label>
                                                <input type="checkbox" name="masajes_seleccionados[]" class="checkbox-masaje" value="{{ $masaje->id }}">
                                                <span>Asignar</span>
                                            </label>
                                        @elseif (Auth::user()->has_role(config('app.admin_role')))
                                            <strong class="red-text">No asignado</strong>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
            @if(Auth::user()->has_role(config('app.masoterapeuta_role')))
                <div id="asignacion-masajes" class="right-align" style="margin-top: 15px;">
                    <span id="contador-masajes">0 Seleccionados</span>
                    <button type="submit" class="btn waves-effect waves-light">
                        Asignarme seleccionados <i class="material-icons right">check</i>
                    </button>
                </div>
            </form>
            @endif



            {{-- Paginación --}}
            <div class="center">
                {{-- {{ $reservasPaginadas->links('vendor.pagination.date') }} --}}
                {{ $reservasPaginadas->appends(request()->query())->links('vendor.pagination.date', ['fechasPaginadas' => $fechasPaginadas]) }}

            </div>
        </div>

    </div>

    <div class="col s12">
        <div class="card-panel">
            @include('themes.backoffice.pages.masaje.includes.horarios_disponibles')
        </div>
    </div>
    </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    @if(session('success'))
    Swal.fire({
        toast: true,
        icon: 'success',
        title: '{{ session('success') }}',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
            didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
            }
    });
    @endif

    @if(session('info'))
    Swal.fire({
        toast: true,
        icon: 'info',
        title: '{{ session('info') }}',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
            didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
            }
    });
    @endif

    @if(session('error'))
    Swal.fire({
        toast: true,
        icon: 'error',
        title: '{{ session('error') }}',
        showConfirmButton: false,
        timer: 5000,
        timerProgressBar: true,
            didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
            }
    });
    @endif
</script>

<script>
    $(document).ready(function () {
        function actualizarEstado() {
            $('.estado').each(function () {
                let estado = $(this);
                let horaActual = new Date();
                let hoy = new Date().toISOString().split('T')[0]; // Fecha de hoy en formato yyyy-mm-dd
                let hora = `${horaActual.getHours().toString().padStart(2, '0')}:${horaActual.getMinutes().toString().padStart(2, '0')}`;

                const fecha = estado.attr('data-fecha'); // Obtener la fecha del masaje
                const horaInicio = estado.attr('data-inicio');
                const horaFin = estado.attr('data-fin');


                let [anio,mes,dia] = hoy.split('-');
                let hoyFormateado = `${dia}-${mes}-${anio}`;

                
                // Solo continuar si la fecha coincide con la fecha actual
                if (fecha === hoyFormateado) {
                    // Convertir horas a Date para comparar
                    let fechaHoraActual = new Date(`${hoy}T${hora}`);
                    let fechaHoraInicio = new Date(`${hoy}T${horaInicio}`);
                    let fechaHoraFin = new Date(`${hoy}T${horaFin}`);

                    if (fechaHoraActual < fechaHoraInicio) {
                        estado.text('Pendiente');
                        estado.removeClass('deep-orange green').addClass('cyan');
                    } else if (fechaHoraActual >= fechaHoraInicio && fechaHoraActual <= fechaHoraFin) {
                        estado.text('En Proceso');
                        estado.removeClass('cyan green').addClass('deep-orange');
                    } else if (fechaHoraActual > fechaHoraFin) {
                        estado.text('Completado');
                        estado.removeClass('cyan deep-orange').addClass('green');
                    }
                }
            });
        }

        // Actualiza el estado cada segundo
        setInterval(actualizarEstado, 1000);
    });
</script>

<script>
    $(document).ready(function () {
        function actualizarUI() {
            let seleccionados = $('.checkbox-masaje:checked').length;

            if (seleccionados > 0) {
                $('#asignacion-masajes').show();
                $('#contador-masajes').text(seleccionados + ' Seleccionados');
            } else {
                $('#asignacion-masajes').hide();
            }
        }

        // Ejecutar al cargar y cuando se haga clic en un checkbox
        actualizarUI();

        $(document).on('change', '.checkbox-masaje', function () {
            actualizarUI();
        });
    });
</script>
@endsection