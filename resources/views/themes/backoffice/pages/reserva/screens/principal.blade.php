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
                        @foreach ($reservas as $idx => $reserva)

                            @foreach ($reserva->visitas->sortBy('horario_sauna') as $visita)
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
                        @foreach($reservas as $idx => $reserva)

                        @foreach ($reserva->visitas->sortBy('horario_tinaja') as $visita)
                            
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

{{-- {{dd(isset($ultimaVisita->masajes),$ultimaVisita->masajes->isEmpty())}} --}}

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
                                                <strong>{{$reserva->cliente->nombre_cliente}} - No Registra masajes </strong>
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



