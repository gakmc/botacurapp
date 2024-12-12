@extends('themes.backoffice.layouts.admin')

@section('title', 'Reservas')

@section('breadcrumbs')
@endsection

@section('dropdown_settings')
<li><a href="{{route ('backoffice.reserva.listar') }}" class="grey-text text-darken-2">Todas las Reservas</a></li>
@endsection

@section('head')
@endsection

@section('content')
<div class="section">
    <a href="?page=1"><p class="caption"><strong>Reservas desde {{ now()->format('d-m-Y') }}</strong></p></a>
    <div class="row"><div class="col s2 green-text"><i class='material-icons left'>fiber_manual_record</i>Pagado</div><div class="col s2 orange-text"><i class='material-icons left'>fiber_manual_record</i>Por pagar Consumo</div> <div class="col s2 blue-text"><i class='material-icons left'>fiber_manual_record</i>Por Pagar</div></div>
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">
            <a href="{{ route('backoffice.reserva.index', ['alternative' => !$alternativeView]) }}" class="waves-effect waves-light btn right">
                @if ($alternativeView)
                Horarios <i class='material-icons right'>list</i>
            @else
                Ubicación <i class='material-icons right'>apps</i>
            @endif</a>

            @if ($alternativeView)

@php
    $color = "";
@endphp
            @foreach($reservasPaginadas as $fecha => $reservas)
            <div class="col s12">
                <h5>Horarios: {{ $fecha }}</h5>
                <div class="row">
                    @foreach($reservas as $reserva)
                        @foreach ($reserva->visitas->sortBy('id_ubicacion') as $visita)

                        @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                        @php
                            $color = "orange";
                        @endphp
                        @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                        @php
                            $color = "green";
                        @endphp
                        @else
                        @php
                            $color = "blue";
                        @endphp
                        @endif

                        <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                    <div class="col s12 m6 l3">
                        <div class="card-panel z-depth-5 animate__animated animate__backInDown"
                            style="--animate-delay: 1s; --animate-duration: 2s;" >
                            
                            <table class="highlight">
                                <thead>
                                    <tr>
                                        <th><h5><i class='material-icons right {{$color}}-text'>fiber_manual_record</i>{{$visita->ubicacion->nombre}}</h5></th>
                                    </tr>
                                </thead>
                                <tbody>

                                        <tr>
                                            <td>
                                                    <strong>Nombre: </strong><strong style="color:#FF4081;">
                                                        {{ $reserva->cliente->nombre_cliente}}
                                                    </strong>
                                                    <p><strong>Programa: </strong>{{ $reserva->programa->nombre_programa }}</p>
                                                    <p><strong>Asistentes: </strong>{{ $reserva->cantidad_personas }} personas</p>
                                                    @if (is_null($reserva->observacion))
                                                        <p><strong>Evento: </strong>Sin Observaciones</p>
                                                    @else
                                                        <p><strong>Evento: </strong><strong style="color:#FF4081;">{{ $reserva->observacion }}</strong></p>
                                                    @endif
                                                    @if (is_null($visita->observacion))
                                                        <p><strong>Requisitos: </strong>Sin Observaciones</p>
                                                    @else
                                                        <p><strong>Requisitos: </strong><strong style="color:#FF4081;">{{ $visita->observacion }}</strong></p>
                                                    @endif
                                                    
                                                </td>
                                            </tr>
                                            
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </a>
                        @endforeach
                @endforeach





                </div>
            </div>
            @endforeach

            
            <!-- Paginación -->
            <div class="center-align">
                {{ $reservasPaginadas->appends(['alternative' => 1])->links('vendor.pagination.materialize') }}
            </div>

                
            @else
                


            @foreach($reservasPaginadas as $fecha => $reservas)
            <div class="col s12">
                <h5>Reservas: {{ $fecha }}</h5>
                <div class="row">
                    <div class="col s12 m6 l4">
                        <div class="card-panel animate__animated animate__backInDown"
                            style="--animate-delay: 1s; --animate-duration: 2s; ">
                            <table class="highlight">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Sauna</th>
                                    </tr>
                                </thead>
                                <tbody>
                            @foreach($reservas as $reserva)
                                @foreach ($reserva->visitas->sortBy('horario_sauna') as $visita)
                                    @if ($visita->horario_sauna)
                                        <tr>
                                            @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                            <td class="orange"></td>
                                            @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                            <td class="green"></td>
                                            @else
                                            <td class="blue"></td>
                                            @endif
                                            <td>
                                                <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                    <strong style="color:#FF4081;">
                                                        {{ $visita->horario_sauna }} - {{ $visita->hora_fin_sauna }}
                                                    </strong>
                                                    <strong>{{ $reserva->cliente->nombre_cliente }} -</strong>
                                                    {{ $visita->ubicacion->nombre }} -
                                                    {{ $reserva->programa->nombre_programa }} -
                                                    {{ $reserva->cantidad_personas }} personas -
                                                    @if (is_null($reserva->observacion))
                                                        Sin Observaciones
                                                    @else
                                                        <strong style="color:#FF4081;">{{ $reserva->observacion }}</strong>
                                                    @endif
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col s12 m6 l4">
                        <div class="card-panel animate__animated animate__backInDown"
                            style="--animate-delay: 2s; --animate-duration: 2s; ">
                            <table class="highlight">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Tinaja</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservas as $reserva)
                                    @foreach ($reserva->visitas as $visita)
                                    @if ($visita->horario_tinaja)
                                    <tr>
                                        @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                        <td class="orange"></td>
                                        @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                        <td class="green"></td>
                                        @else
                                        <td class="blue"></td>
                                        @endif
                                        
                                        <td>
                                            <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                <strong style="color:#FF4081;">{{ $visita->horario_tinaja }} - {{
                                                    $visita->hora_fin_tinaja }}</strong>
                                                <strong>{{ addslashes($reserva->cliente->nombre_cliente) }} -</strong>
                                                {{$visita->ubicacion->nombre}} -
                                                {{ $reserva->programa->nombre_programa }} -
                                                {{ $reserva->cantidad_personas }} personas -
                                                @if (is_null($reserva->observacion))
                                                Sin Observaciones
                                                @else
                                                <strong style="color:#FF4081;">{{$reserva->observacion}}</strong>
                                                @endif
                                            </a>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="col s12 m6 l4">
                        <div class="card-panel animate__animated animate__backInDown"
                            style="--animate-delay: 3s; --animate-duration: 2s; ">
                            <table class="highlight">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Masaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservas as $reserva)
                                    @foreach ($reserva->visitas as $visita)
                                    @if ($visita->horario_masaje)
                                    <tr>
                                        @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                        <td class="orange"></td>
                                        @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                        <td class="green"></td>
                                        @else
                                        <td class="blue"></td>
                                        @endif
                                        <td>
                                            <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                <strong style="color: #FF4081">{{ $visita->horario_masaje }} - 
                                                    @if (!$reserva->programa->servicios->contains('nombre_servicio', 'Masaje') && $visita->horario_masaje)
                                                        {{$visita->hora_fin_masaje_extra}}
                                                    @else
                                                        {{$visita->hora_fin_masaje }}    
                                                    @endif
                                                </strong>
                                                <strong>{{ $reserva->cliente->nombre_cliente }} -</strong>
                                                {{$visita->ubicacion->nombre}} -
                                                {{ $reserva->programa->nombre_programa }} -
                                                {{ $reserva->cantidad_personas }} personas -
                                                @if (is_null($reserva->observacion))
                                                Sin Observaciones
                                                @else
                                                <strong style="color:#FF4081;">{{$reserva->observacion}}</strong>
                                                @endif
                                            </a>
                                        </td>
                                    </tr>
                                    @elseif (is_null($visita->horario_masaje))

                                    <tr>
                                        <td>
                                            <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                <strong>{{ $reserva->cliente->nombre_cliente }} -</strong> No registra
                                                masaje
                                            </a>
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach

            <!-- Paginación -->
            <div class="center-align">
                {{ $reservasPaginadas->links('vendor.pagination.materialize') }}
            </div>



            @endif
        </div>
    </div>
</div>
@endsection

@section('foot')


@endsection