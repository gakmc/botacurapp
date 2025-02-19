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
    <div class="row"><div class="col s2 green-text offset-s2"><i class='material-icons left'>fiber_manual_record</i>Pagado</div><div class="col s2 orange-text offset-s1"><i class='material-icons left'>fiber_manual_record</i>Por pagar Consumo</div> <div class="col s2 blue-text offset-s1"><i class='material-icons left'>fiber_manual_record</i>Por Pagar</div></div>
    
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="card-panel ">
            <a href="{{ route('backoffice.reserva.index', ['alternative' => !$alternativeView]) }}" class="waves-effect waves-light btn right hide-on-small-only hide-on-med-only">
            @if ($alternativeView)
                Horarios <i class='material-icons right'>list</i>
            @else
                Ubicación <i class='material-icons right'>apps</i>
            @endif</a>
            
            {{-- Vista Alternativa --}}
            @if ($alternativeView)

                @php
                    $color = "";
                @endphp

                {{-- Vista Alternativa en Pantallas L --}}
                @foreach($reservasPaginadas as $fecha => $reservas)
                    <div class="col s12">
                        <h5>Horarios: {{ $fecha }}</h5>
                        <div class="row">

                            @foreach($reservas as $reserva)
                            
                            @php

                                    $horariosSauna = $reserva->visitas->pluck('horario_sauna')->filter()->unique()->join(', '); // Obtener horarios de sauna
                                    $horariosTinaja = $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->join(', '); // Obtener horarios de tinaja
                                    $ubicacion = [];
                                    
                                    foreach ($reserva->visitas->sortBy('ubicacion.id') as $index=>$visita) {

                                        $ubicacion[] = $visita->ubicacion->nombre ?? 'No registra';
                                        if ($visita->masajes->isEmpty()) {
                                            $horariosMasaje = null;
                                        }else {
                                            $horariosMasaje = $visita->masajes->pluck('horario_masaje')->filter()->unique()->join(', '); // Obtener horarios de masaje
                                        }
                                        
                                    }
                                    
                                    
                                    if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa)) {
                                        $color = "orange";
                                    }elseif($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa)) {
                                        $color = "green";
                                    }else{
                                        $color = "blue";
                                    }
                                @endphp

                                <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                            <div class="col s12 m6 l3">
                                <div class="card-panel z-depth-5 animate__animated animate__backInDown"
                                    style="--animate-delay: 1s; --animate-duration: 2s;" >
                                    
                                    <table class="highlight">
                                        <thead>
                                            <tr>
                                                <th><h5><i class='material-icons right {{$color}}-text'>fiber_manual_record</i>{{ isset($ubicacion[$index]) ? $ubicacion[$index] : 'No Disponible' }}</h5></th>
                                                @if ($visita->trago_cortesia === "Si")
                                                    <th><h5><i class='material-icons' style="color: #FF4081;">local_bar</i></h5></th>
                                                @endif
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




                                                            <p><strong>Sauna: </strong>{{ $horariosSauna ?? 'No Registra Sauna' }} </p>
                                                            <p><strong>Tinaja: </strong>{{ $horariosTinaja ?? 'No Registra Tinaja' }} </p>
                                                            <p><strong>Masaje: </strong>{{ $horariosMasaje ?? 'No Registra Masajes' }} </p>
                                                        </td>
                                                    </tr>
                                                    
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </a>

                                
                                
                        @endforeach

                        </div>
                    </div>
                @endforeach
                {{-- Fin Vista Alternativa en Pantallas L --}}

                
                <!-- Paginación -->
                <div class="center-align">
                    {{ $reservasPaginadas->appends(['alternative' => 1])->links('vendor.pagination.materialize') }}
                </div>

                {{-- Fin Vista Alternativa --}}    
            @else
                {{-- Vista Comun --}}    
                {{-- Vista en Pantallas de dispositivos Moviles --}}
                @foreach($reservasMovilesPaginadas as $fecha => $reservas)
                    <div class="col s12 hide-on-large-only">
                        <h5>Reservas: {{ $fecha }}</h5>
                        <div class="row">
                    
                            @foreach($reservas as $reserva)
                    
                                @php
                    
                                    $horariosSauna = $reserva->visitas->pluck('horario_sauna')->filter()->unique()->join(', '); // Obtener horarios de sauna
                                    $horariosTinaja = $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->join(', '); // Obtener horarios de tinaja
                                    $ubicacion = [];
                            
                                    foreach ($reserva->visitas->sortBy('id_ubicacion') as $horario=>$visita) {
                                        $ubicacion[] = $visita->ubicacion->nombre ?? 'No registra';
                            
                                        if ($visita->masajes->isEmpty()) {
                                            $horariosMasaje = null;
                                        }else {
                                            $horariosMasaje = $visita->masajes->pluck('horario_masaje')->filter()->unique()->join(', '); // Obtener horarios de masaje
                                        }
                            
                                    }
                            
                                    if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa)) {
                                        $color = "orange";
                                    }elseif($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa)) {
                                        $color = "green";
                                    }else{
                                        $color = "blue";
                                    }

                                @endphp
                    
                    
                                    <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                        <div class="col s12 m6">
                                            <div class="card-panel z-depth-5 animate__animated animate__backInLeft"
                                                style="--animate-delay: 1s; --animate-duration: 2s;">
                    
                                                <table class="highlight">
                                                    <thead>
                                                        <tr>
                                                            <th>
                                                                <h5><i class='material-icons right {{$color}}-text'>fiber_manual_record</i>
                                                                {{isset($ubicacion[$horario ?? 0]) ? $ubicacion[$horario ?? 0] : 'No Disponible' }}
                                                                </h5>
                                                            </th>
                                                            @isset($visita)
                                                                
                                                            
                                                                @if ($visita->trago_cortesia === "Si")
                                                                <th>
                                                                    <h5>
                                                                        <i class='material-icons' style="color: #FF4081;">local_bar</i>
                                                                    </h5>
                                                                </th>
                                                                @endif
                                                            @endisset
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                    
                                                        <tr>
                                                            <td>
                                                                <strong>Nombre: </strong><strong style="color:#FF4081;">
                                                                    {{ $reserva->cliente->nombre_cliente}}
                                                                </strong>
                                                                <p><strong>Programa: </strong>{{ $reserva->programa->nombre_programa }}</p>
                                                                <p><strong>Asistentes: </strong>{{ $reserva->cantidad_personas }} personas
                                                                </p>
                                                                @if (is_null($reserva->observacion))
                                                                <p><strong>Evento: </strong>Sin Observaciones</p>
                                                                @else
                                                                <p><strong>Evento: </strong><strong style="color:#FF4081;">{{
                                                                        $reserva->observacion }}</strong></p>
                                                                @endif

                                                                @isset($visita)
                                                                    @if (is_null($visita->observacion))
                                                                        <p><strong>Requisitos: </strong>Sin Observaciones</p>
                                                                    @else
                                                                        <p><strong>Requisitos: </strong><strong style="color:#FF4081;">{{
                                                                            $visita->observacion }}</strong></p>
                                                                    @endif
                                                                @endisset
                                                                <p><strong>Sauna: </strong>{{ $horariosSauna ?? 'No Registra' }} </p>
                                                                <p><strong>Tinaja: </strong>{{ $horariosTinaja ?? 'No Registra' }} </p>
                                                                <p><strong>Masaje: </strong>{{ $horariosMasaje ?? 'No Registra Masajes' }} </p>
                    
                                                            </td>
                                                        </tr>
                    
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </a>
                    
                                    @endforeach
                    
                        </div>
                    </div>

                @endforeach
                {{-- Fin Vista en Pantallas de dispositivos Moviles --}}


                {{-- Vista en Pantallas L --}}
                @foreach($reservasPaginadas as $fecha => $reservas)
                    <div class="col s12 hide-on-small-only hide-on-med-only">
                    <h5>Reservas: {{ $fecha }}</h5>
                    <div class="row">
                        {{-- Sauna --}}
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
                                        @foreach ($reservas->sortBy('horario_sauna') as $idx => $reserva)
                                            @php
                                                $visitas = $reserva->visitas;
                                            @endphp

                                            @foreach ($visitas as $visita)
                                        <tr>
                                            @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                            <td class="orange" style="border-radius: 5px">
                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                    {{ $visita->horario_sauna }}  
                                                    <i class='tiny material-icons center'>arrow_downward</i>  
                                                    {{$visita->hora_fin_sauna }}
                                                </strong>
                                            </td>
                                            @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                            <td class="green" style="border-radius: 5px">
                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                    {{ $visita->horario_sauna }}  
                                                    <i class='tiny material-icons center'>arrow_downward</i>  
                                                    {{$visita->hora_fin_sauna }}
                                                </strong>
                                            </td>
                                            @else
                                            <td class="blue" style="border-radius: 5px">
                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                    {{ $visita->horario_sauna }}  
                                                    <i class='tiny material-icons center'>arrow_downward</i>  
                                                    {{$visita->hora_fin_sauna }}
                                                </strong>
                                            </td>
                                            @endif
                                            <td>
                                                <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                    <strong>{{ addslashes($reserva->cliente->nombre_cliente) }} -</strong>
                                                    {{$visita->ubicacion->nombre ?? 'No registra'}} -
                                                    {{ $reserva->programa->nombre_programa }} -
                                                    {{ $reserva->cantidad_personas }} personas -
                                                    @if (is_null($reserva->observacion))
                                                    Sin Observaciones
                                                    @else
                                                    <strong style="color:#FF4081;">{{$reserva->observacion}}</strong>
                                                    @endif
                                                </a>
                                            </td>
                                            @if ($visita->trago_cortesia === "Si")
                                                <td>
                                                    <i class="material-icons" style="color: #FF4081">local_bar</i>
                                                </td>
                                            @endif
                                        </tr>
                                        @endforeach
                                    @endforeach
                                    
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- Tinaja --}}
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
                                        @foreach($reservas->sortBy('horario_tinaja') as $idx => $reserva)
                                        @php
                                            $visitas = $reserva->visitas;
                                        @endphp
                                        @foreach ($visitas as $visita)
                                            
                                        <tr>
                                            @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                            <td class="orange" style="border-radius: 5px">
                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                    {{ $visita->horario_tinaja }}  
                                                    <i class='tiny material-icons center'>arrow_downward</i>  
                                                    {{$visita->hora_fin_tinaja }}
                                                </strong>
                                            </td>
                                            @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                            <td class="green" style="border-radius: 5px">
                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                    {{ $visita->horario_tinaja }}  
                                                    <i class='tiny material-icons center'>arrow_downward</i>  
                                                    {{$visita->hora_fin_tinaja }}
                                                </strong>
                                            </td>
                                            @else
                                            <td class="blue" style="border-radius: 5px">
                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                    {{ $visita->horario_tinaja }}  
                                                    <i class='tiny material-icons center'>arrow_downward</i>  
                                                    {{$visita->hora_fin_tinaja }}
                                                </strong>
                                            </td>
                                            @endif
                                            
                                            <td>
                                                <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                    <strong>{{ addslashes($reserva->cliente->nombre_cliente) }} -</strong>
                                                    {{$visita->ubicacion->nombre ?? 'No registra'}} -
                                                    {{ $reserva->programa->nombre_programa }} -
                                                    {{ $reserva->cantidad_personas }} personas -
                                                    @if (is_null($reserva->observacion))
                                                    Sin Observaciones
                                                    @else
                                                    <strong style="color:#FF4081;">{{$reserva->observacion}}</strong>
                                                    @endif
                                                </a>
                                            </td>
                                            @if ($visita->trago_cortesia === "Si")
                                                <td>
                                                    <i class="material-icons" style="color: #FF4081">local_bar</i>
                                                </td>
                                            @endif
                                        </tr>
                                        
                                        
                                        @endforeach
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- Masaje --}}
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
                                        @php
                                            // Crear un array temporal para agrupar masajes por horario y cliente
                                            $horariosAgrupados = [];

                                            // Arreglo para almacenar la ultima visita por id_reserva
                                            $ultimaVisitaPorReserva = [];
                                        @endphp

                                        @foreach($reservas as $reserva)
                                            @foreach ($reserva->visitas as $visita)
                                                @php
                                                    // Almacenar la última visita de cada reserva
                                                    $ultimaVisitaPorReserva[$visita->id_reserva] = $visita;
                                                @endphp
                                            @endforeach
                                        @endforeach
                                
                                        @foreach($reservas as $reserva)
                                            @if (isset($ultimaVisitaPorReserva[$reserva->id]))
                                                @php
                                                    $ultimaVisita = $ultimaVisitaPorReserva[$reserva->id];
                                                @endphp

                                                @if ($ultimaVisita->masajes->isEmpty())
                                                    <tr>
                                                        @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                                            <td class="orange" style="border-radius: 5px">
                                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                                    <i class='tiny material-icons center'>do_not_disturb_alt</i>
                                                                </strong>
                                                            </td>
                                                        @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                                            <td class="green" style="border-radius: 5px">
                                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                                    <i class='tiny material-icons center'>do_not_disturb_alt</i>
                                                                </strong>
                                                            </td>
                                                        @else
                                                            <td class="blue" style="border-radius: 5px">
                                                                <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                                    <i class='tiny material-icons center'>do_not_disturb_alt</i>
                                                                </strong>
                                                            </td>
                                                        @endif
                                                    
                                                        <td>
                                                            <a href="#" onclick="activar_alerta(`{{$reserva->cliente->nombre_cliente}}`)">
                                                                <strong>{{$reserva->cliente->nombre_cliente}} - No Registra masajes (Problemas)</strong>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach ($ultimaVisita->masajes as $masaje)
                                                        @if ($masaje->horario_masaje)
                                                            @php
                                                                // Clave para agrupar: horario y cliente
                                                                $clave = $masaje->horario_masaje . '_' . $reserva->cliente->nombre_cliente;
                                                                
                                                                // Si la clave no existe, crearla
                                                                if (!isset($horariosAgrupados[$clave])) {
                                                                    $horariosAgrupados[$clave] = [
                                                                        'horario_inicio' => $masaje->horario_masaje,
                                                                        'horario_fin' => (!$reserva->programa->servicios->contains('nombre_servicio', 'Masaje') && $masaje->horario_masaje) ? $masaje->hora_fin_masaje_extra : $masaje->hora_fin_masaje,
                                                                        'cliente' => $reserva->cliente->nombre_cliente,
                                                                        'ubicacion' => $ultimaVisita->ubicacion->nombre ?? 'No registra',
                                                                        'trago' => $ultimaVisita->trago_cortesia,
                                                                        'programa' => $reserva->programa->nombre_programa,
                                                                        'personas' => [],
                                                                        'observacion' => $reserva->observacion
                                                                    ];
                                                                }
                                                                
                                                                // Agregar el número de persona
                                                                $horariosAgrupados[$clave]['personas'][] = $masaje->persona;
                                                            @endphp
                                                        @endif
                                                    @endforeach
                                                @endif
                                            @endif
                                        @endforeach
                                
                                        @foreach($horariosAgrupados as $horario)
                                        <tr>
                                                @if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa))
                                                <td class="orange" style="border-radius: 5px">
                                                    <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                        {{ $horario['horario_inicio'] }}
                                                        <i class='tiny material-icons center'>arrow_downward</i>
                                                        {{ $horario['horario_fin'] }}
                                                    </strong>
                                                </td>
                                                @elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa))
                                                <td class="green" style="border-radius: 5px">
                                                    <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                        {{ $horario['horario_inicio'] }}
                                                        <i class='tiny material-icons center'>arrow_downward</i>
                                                        {{ $horario['horario_fin'] }}
                                                    </strong>
                                                </td>
                                                @else
                                                <td class="blue" style="border-radius: 5px">
                                                    <strong style="color:#F5F5F5; display:flex; justify-content: center; flex-direction:column">
                                                        {{ $horario['horario_inicio'] }}
                                                        <i class='tiny material-icons center'>arrow_downward</i>
                                                        {{ $horario['horario_fin'] }}
                                                    </strong>
                                                </td>
                                                @endif
                                                <td>
                                                    <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                                        <strong>{{ $horario['cliente'] }} -</strong>
                                                        {{ $horario['ubicacion'] }} -
                                                        {{ $horario['programa'] }} -
                                                        persona {{ implode(' y ', $horario['personas']) }} -
                                                        @if (is_null($horario['observacion']))
                                                            Sin Observaciones
                                                        @else
                                                            <strong style="color:#FF4081;">{{ $horario['observacion'] }}</strong>
                                                        @endif
                                                    </a>
                                                </td>
                                                @if ($horario['trago'] === "Si")
                                                    <td>
                                                        <i class="material-icons" style="color: #FF4081">local_bar</i>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>


                            </div>
                        </div>
                    </div>
                    </div>


                @endforeach
                {{-- Fin Vista Pantallas L --}}

                <!-- Paginación -->
                <div class="center-align">
                    {{ $reservasPaginadas->links('vendor.pagination.materialize') }}
                </div>
                {{-- Fin Vista Comun --}}
            @endif
        </div>
    </div>
</div>
@endsection

@section('foot')
<script>
    function activar_alerta(cliente)
    {
        console.log(cliente);
        
        Swal.fire({
            toast: true,
            icon: 'warning',
            title: `${cliente} no registra masajes`,
            color: 'white',
            iconColor: 'white',
            background: "#039B7B",
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
                didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
                }
        });
    }
   </script>
@endsection