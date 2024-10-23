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
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">

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
                                        <th>Sauna</th>
                                    </tr>
                                </thead>
                                <tbody>
                            @foreach($reservas as $reserva)
                                @foreach ($reserva->visitas->sortBy('horario_sauna') as $visita)
                                    @if ($visita->horario_sauna)
                                        <tr>
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
                                        <th>Tinaja</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservas as $reserva)
                                    @foreach ($reserva->visitas as $visita)
                                    @if ($visita->horario_tinaja)
                                    <tr>
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
                                        <th>Masaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservas as $reserva)
                                    @foreach ($reserva->visitas as $visita)
                                    @if ($visita->horario_masaje)
                                    <tr>
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
        </div>
    </div>
</div>
@endsection

@section('foot')


@endsection