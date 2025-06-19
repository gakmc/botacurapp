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
                    @foreach ($reservasPaginadas as $fecha=>$reservasPorFecha)

                    <h5>@if (now()->format('d-m-Y') == $fecha)
                        <strong>Hoy</strong>
                        @endif
                        {{$fecha}}
                    </h5>

                    @foreach ($reservasPorFecha as $reserva)
                    @if (isset($distribucionHorarios[$reserva->id]))
                    @else
                    <p>No hay horarios disponibles para esta visita.</p>
                    @endif



                    
                    @foreach ($reserva->masajes->sortBy(function($m) {
                        return $m->horario_masaje . '-' . $m->persona;
                    }) as $masaje)

                    <tr>
                        @if(Auth::user()->has_role(config('app.masoterapeuta_role')))
                            <form action="{{ route('backoffice.masaje.asignar_multiples') }}" method="POST">
                                @csrf
                        @endif
                        <td>
                            
                            {{$masaje->horario_masaje}} -
                            @if ($masaje->hora_fin_masaje)
                            {{$masaje->hora_fin_masaje}}
                            @else
                            {{$masaje->hora_fin_masaje_extra}}
                            @endif
                        </td>
                        <td>{{ $reserva->cliente->nombre_cliente }}</td>
                        <td>Persona {{$masaje->persona}}</td>
                        <td>{{ $masaje->tipo_masaje }}</td>
                        <td>{{ $masaje->lugarMasaje->nombre }}</td>
                        <td>
                            <span class="estado badge white-text cyan" data-fecha="{{$reserva->fecha_visita}}"
                                data-inicio="{{ $masaje->horario_masaje }}" @if ($masaje->hora_fin_masaje)
                                data-fin="{{ $masaje->hora_fin_masaje }}"
                                @else
                                data-fin="{{ $masaje->hora_fin_masaje_extra }}"
                                @endif
                                >Pendiente</span>
                        </td>
                        <td>
                            @php
                            // Buscar el masaje correspondiente a la persona actual ($i)
                            $masajeAsignado = $masaje->load('user');
                            //dd($masajeAsignado->user);
                            @endphp

                            {{-- @if ($masajeAsignado && $masajeAsignado->user)
                                <strong style="color:#039B7B;">{{ $masajeAsignado->user->name }}</strong>
                            @else
                                @if (Auth::user()->has_role(config('app.masoterapeuta_role')))
                                <!-- Mostrar el botón si el usuario es masoterapeuta y no hay masoterapeuta asignado -->
                                <form action="{{ route('backoffice.masaje.update', $masaje) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="id" value="{{ $masaje->id }}">
                                    <button type="submit" class="btn-floating" {{$fecha==now()->format('d-m-Y') ? '' :
                                        'disabled'}}>
                                        <i class="material-icons">pan_tool</i>
                                    </button>
                                </form>
                                @elseif (Auth::user()->has_role(config('app.admin_role')))
                                <strong class="red-text">No asignado</strong>
                                @endif
                            @endif --}}



                            @if ($masajeAsignado && $masajeAsignado->user)
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




                            {{-- @if (Auth::user()->has_role(config('app.masoterapeuta_role')))
                            <form action="{{ route('backoffice.masaje.update', $masaje) }}" method="POST">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="id" value="{{ $masaje->id }}">
                                <button type="submit" class="btn-floating">
                                    <i class="material-icons">pan_tool</i>
                                </button>
                            </form>
                            @elseif (Auth::user()->has_role(config('app.admin_role')))
                            <strong class="red-text">No asignado</strong>
                            @endif --}}


                        </td>

                    </tr>
                    @endforeach


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