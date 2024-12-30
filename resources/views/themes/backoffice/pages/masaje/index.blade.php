@extends('themes.backoffice.layouts.admin')

@section('title', 'Masajes')

@section('breadcrumbs')
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Masajes desde <a href="?page=1">{{ now()->format('d-m-Y') }}</a></strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
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

                    @if (!is_null($reserva->horario_masaje))
                    
                        <tr>
                            <td>
                                {{$reserva->horario_masaje}} -
                                @if ($reserva->hora_fin_masaje)
                                {{$reserva->hora_fin_masaje}}
                                @else
                                {{$reserva->hora_fin_masaje_extra}}
                                @endif
                            </td>
                            <td>{{ $reserva->cliente->nombre_cliente }}</td>
                            <td>{{$reserva->cantidad_personas}} Personas total</td>
                            <td>{{ $reserva->tipo_masaje }}</td>
                            <td>{{ $reserva->lugarMasaje }}</td>
                            <td>
                                <span class="estado badge white-text cyan" data-fecha="{{$reserva->fecha_visita}}"
                                    data-inicio="{{ $reserva->horario_masaje }}" @if ($reserva->hora_fin_masaje)
                                    data-fin="{{ $reserva->hora_fin_masaje }}"
                                    @else
                                    data-fin="{{ $reserva->hora_fin_masaje_extra }}"
                                    @endif
                                    >Pendiente</span>
                            </td>
                            <td>
                                {{-- @php
                                // Buscar el masaje correspondiente a la persona actual ($i)
                                $masajeAsignado = $reserva->visita->masajes->firstWhere('persona');
                                @endphp

                                @if ($masajeAsignado && $masajeAsignado->user)
                                <strong style="color:#039B7B;">{{ $masajeAsignado->user->name }}</strong>
                                @else
                                @if (Auth::user()->has_role(config('app.masoterapeuta_role')))
                                <!-- Mostrar el botón si el usuario es masoterapeuta y no hay masoterapeuta asignado -->
                                <form action="{{ route('backoffice.masaje.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="id_visita" value="{{ $visita->id }}">
                                    <input type="hidden" name="persona_numero" value="">
                                    <button type="submit" class="btn-floating" {{$fecha == now()->format('d-m-Y') ? '' : 'disabled'}}>
                                        <i class="material-icons">pan_tool</i>
                                    </button>
                                </form>
                            @elseif (Auth::user()->has_role(config('app.admin_role')))
                                <strong class="red-text">No asignado</strong>
                            @endif
                                @endif --}}






                                @if (Auth::user()->has_role(config('app.masoterapeuta_role')))
                                    <form action="{{ route('backoffice.masaje.store') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="id_visita" value="{{ $reserva->id }}">
                                        <input type="hidden" name="persona_numero" value="{{ $persona }}">
                                        <button type="submit" class="btn-floating">
                                            <i class="material-icons">pan_tool</i>
                                        </button>
                                    </form>
                                @elseif (Auth::user()->has_role(config('app.admin_role')))
                                    <strong class="red-text">No asignado</strong>
                                @endif
                            </td>

                        </tr>
                        
                        @endif
                        @endforeach
                        
                        @endforeach
                </tbody>
            </table>

            {{-- Paginación --}}
            <div class="center">
                {{ $reservasPaginadas->links('vendor.pagination.materialize') }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
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

@endsection