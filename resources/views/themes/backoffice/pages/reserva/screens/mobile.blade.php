@if ($mobileView == 'masajes')
    <div class="col s12 hide-on-large-only">
        <h5>Masajes: {{ $fecha }}</h5>
        <div class="row">
                <!-- Cada reserva tiene un `data-tipo` para filtrarlas con jQuery -->
                {{-- <a href="{{ route('backoffice.reserva.show', $reserva) }}"> --}}
            <div class="col s12 reserva-card" data-tipo="sauna">
                <div class="card-panel z-depth-5 animate__animated animate__backInLeft"
                    style="--animate-delay: 1s; --animate-duration: 2s;">
                    
                    <table class="">
                        <thead>
                            <tr>
                                <th>Hora Ingreso</th>
                                <th>Hora Salida</th>
                                <th>Cantidad Personas</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservas as $reserva)
                            @php
                            $ubicacion = [];
                            $horariosMasaje = collect();
                            $mostrarReserva = false;
                    
                            foreach ($reserva->visitas->sortBy('id_ubicacion') as $horario => $visita) {
                                $ubicacion[] = $visita->ubicacion->nombre ?? 'No registra';
                                
                                if (!$reserva->masajes->isEmpty()) {
                                    $horariosMasaje = $reserva->masajes->pluck('horario_masaje')->filter()->unique();
                                    $mostrarReserva = true;
                                }
                            }
                    
                            // Solo se procesan los horarios si hay masajes
                            if ($mostrarReserva) {
                                $horariosMasajeFin = $horariosMasaje->map(function ($horario) {
                                    return \Carbon\Carbon::parse($horario)->addMinutes(30)->format('H:i');
                                })->implode(', ');
                    
                                $horariosMasaje = $horariosMasaje->implode(', ');
                            }
                    
                            if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa)) {
                                $color = "orange";
                            } elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa)) {
                                $color = "green";
                            } else {
                                $color = "blue";
                            }
                        @endphp
                                    @if ($mostrarReserva)
                                    <tr>
                                        <td>
                                            <p><strong>{{ $horariosMasaje ?: 'No Registra' }}</strong></p>
                                        </td>
                                        <td>
                                            <p><strong>{{ $horariosMasajeFin ?: 'No Registra' }}</strong></p>
                                        </td>
                                        <td>
                                            {{ $reserva->cantidad_personas }}
                                        </td>
                                        <td>
                                            {{ $reserva->cliente->nombre_cliente }}
                                        </td>
                                        <td>
                                            <i class='material-icons right {{ $color }}-text'>fiber_manual_record</i>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


@elseif($mobileView === 'ubicacion')
    <div class="col s12 hide-on-large-only">
        <h5>Ubicaciones: {{ $fecha }}</h5>
        <div class="row">
                <!-- Cada reserva tiene un `data-tipo` para filtrarlas con jQuery -->
                
            <div class="col s12 reserva-card" data-tipo="sauna">
                <div class="card-panel z-depth-5 animate__animated animate__backInLeft"
                    style="--animate-delay: 1s; --animate-duration: 2s;">
                    
                    <table class="">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad Personas</th>
                                <th>Lugar</th>
                                <th>Programa</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservas as $reserva)
                                @php
                                    $ubicacion = [];
                                    
                                    foreach ($reserva->visitas->sortBy('id_ubicacion') as $horario => $visita) {
                                        $ubicacion[] = $visita->ubicacion->nombre ?? 'No registra';
                                        
                                        if ($reserva->masajes->isEmpty()) {
                                            $horariosMasaje = null;
                                        } else {
                                            $horariosMasaje = $reserva->masajes->pluck('horario_masaje')->filter()->unique();

                                            $horariosMasajeFin = $horariosMasaje->map(function ($horario) {
                                                return \Carbon\Carbon::parse($horario)->addMinutes(30)->format('H:i');
                                            })->implode(', ');

                                            $horariosMasaje = $horariosMasaje->implode(', ');
                                        }
                                    }
                                    
                                    if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa)) {
                                        $color = "orange";
                                    } elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa)) {
                                        $color = "green";
                                    } else {
                                        $color = "blue";
                                    }
                                @endphp
                                <tr>
                                    <a href="{{ route('backoffice.reserva.show', $reserva) }}">
                                    <td>
                                        {{ (isset($ubicacion)) ? $reserva->cliente->nombre_cliente : 'No Registra' }}
                                    </td>
                                    <td>
                                   {{ (isset($ubicacion)) ? $reserva->cantidad_personas : 'No Registra' }}
                                    </td>
                                    <td>
                                        {{ isset($ubicacion[$horario ?? 0]) ? $ubicacion[$horario ?? 0] : 'No Disponible' }}
                                    </td>
                                    <td>
                                        {{ (isset($ubicacion)) ? $reserva->programa->nombre_programa : 'No Registra' }}
                                    </td>
                                    <td>
                                        <i class='material-icons right {{ $color }}-text'>fiber_manual_record</i>
                                    </td>
                                    </a>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
@else
    <div class="col s12 hide-on-large-only">
        <h5>SPA: {{ $fecha }}</h5>
        <div class="row">
            <!-- Cada reserva tiene un `data-tipo` para filtrarlas con jQuery -->
            {{-- <a href="{{ route('backoffice.reserva.show', $reserva) }}"> --}}
            <div class="col s12  reserva-card" data-tipo="sauna">
                <div class="card-panel z-depth-5 animate__animated animate__backInLeft"
                style="--animate-delay: 1s; --animate-duration: 2s;">
                    <h5><strong>Sauna</strong></h5>
                    <table class="">
                        <thead>
                            <tr>
                                <th>
                                    Hora Ingreso
                                    {{-- <h5><i class='material-icons right {{ $color }}-text'>fiber_manual_record</i>
                                        Spa: {{ ($horariosSauna != '') ? $horariosSauna : 'No Registra' }} - 
                                        {{ isset($ubicacion[$horario ?? 0]) ? $ubicacion[$horario ?? 0] : 'No Disponible' }}
                                    </h5> --}}
                                </th>
                                <th>Hora Salida</th>
                                <th>Cantidad Personas</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                                {{-- @isset($visita)
                                    @if ($visita->trago_cortesia === "Si")
                                        <th>
                                            <h5>
                                                <i class='material-icons' style="color: #FF4081;">local_bar</i>
                                            </h5>
                                        </th>
                                    @endif
                                @endisset --}}
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservas as $reserva)
                                @php
                                    $horariosSauna = $reserva->visitas->pluck('horario_sauna')->filter()->unique()->join(', ');
                                    $horariosTinaja = $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->join(', ');
                                    
                                    $ubicacion = [];
                                    
                                    foreach ($reserva->visitas->sortBy('id_ubicacion') as $horario => $visita) {
                                        $ubicacion[] = $visita->ubicacion->nombre ?? 'No registra';
                                        
                                        if ($reserva->masajes->isEmpty()) {
                                            $horariosMasaje = null;
                                        } else {
                                            $horariosMasaje = $reserva->masajes->pluck('horario_masaje')->filter()->unique()->join(', ');
                                        }
                                    }
                                    
                                    if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa)) {
                                        $color = "orange";
                                    } elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa)) {
                                        $color = "green";
                                    } else {
                                        $color = "blue";
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <p><strong>{{ ($horariosSauna != '') ? $horariosSauna : 'No Registra' }}</strong></p>
                                    </td>
                                    <td>
                                        <p><strong>{{ ($horariosSauna != '') ? $visita->hora_fin_sauna : 'No Registra' }}</strong></p>
                                    </td>
                                    <td>
                                        {{ ($horariosSauna != '') ? $reserva->cantidad_personas : 'No Registra' }}
                                    </td>
                                    <td>
                                        {{ ($horariosSauna != '') ? $reserva->cliente->nombre_cliente : 'No Registra' }}
                                    </td>
                                    <td>
                                        <i class='material-icons right {{ $color }}-text'>fiber_manual_record</i>
                                    </td>
                                    {{-- <td>
                                        <strong>Nombre: </strong><strong style="color:#FF4081;">
                                            {{ $reserva->cliente->nombre_cliente }}
                                        </strong>
                                        <p><strong>Programa: </strong>{{ $reserva->programa->nombre_programa }}</p>
                                        <p><strong>Asistentes: </strong>{{ $reserva->cantidad_personas }} personas</p>

                                        @if (is_null($reserva->observacion))
                                            <p><strong>Evento: </strong>Sin Observaciones</p>
                                        @else
                                            <p><strong>Evento: </strong><strong style="color:#FF4081;">{{ $reserva->observacion }}</strong></p>
                                        @endif

                                        @isset($visita)
                                            @if (is_null($visita->observacion))
                                                <p><strong>Requisitos: </strong>Sin Observaciones</p>
                                            @else
                                                <p><strong>Requisitos: </strong><strong style="color:#FF4081;">{{ $visita->observacion }}</strong></p>
                                            @endif
                                        @endisset
                                        
                                        <p><strong>Sauna: </strong>{{ ($horariosSauna != '') ? $horariosSauna : 'No Registra' }} </p>
                                        <p><strong>Tinaja: </strong>{{ ($horariosTinaja != '') ? $horariosTinaja : 'No Registra' }} </p>
                                        <p><strong>Masaje: </strong>{{ $horariosMasaje ?? 'No Registra Masajes' }} </p>
                                    </td> --}}
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>


                <div class="card-panel z-depth-5 animate__animated animate__backInLeft"
                    style="--animate-delay: 1s; --animate-duration: 2s;">
                    <h5><strong>Tinaja</strong></h5>
                    <table class="">
                        <thead>
                            <tr>
                                <th>Hora Ingreso</th>
                                <th>Hora Salida</th>
                                <th>Cantidad Personas</th>
                                <th>Nombre</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservas as $reserva)
                                @php
                                    $horariosSauna = $reserva->visitas->pluck('horario_sauna')->filter()->unique()->join(', ');
                                    $horariosTinaja = $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->join(', ');
                                    
                                    $ubicacion = [];
                                    
                                    foreach ($reserva->visitas->sortBy('id_ubicacion') as $horario => $visita) {
                                        $ubicacion[] = $visita->ubicacion->nombre ?? 'No registra';
                                        
                                        if ($reserva->masajes->isEmpty()) {
                                            $horariosMasaje = null;
                                        } else {
                                            $horariosMasaje = $reserva->masajes->pluck('horario_masaje')->filter()->unique()->join(', ');
                                        }
                                    }
                                
                                    if ($reserva->venta->total_pagar <= 0 && is_null($reserva->venta->diferencia_programa)) {
                                            $color = "orange";
                                    } elseif ($reserva->venta->total_pagar <= 0 && !is_null($reserva->venta->diferencia_programa)) {
                                            $color = "green";
                                    } else {
                                            $color = "blue";
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <p><strong>{{ ($horariosTinaja != '') ? $horariosTinaja : 'No Registra' }}</strong></p>
                                    </td>
                                    <td>
                                        <p><strong>{{ ($horariosTinaja != '') ? $visita->hora_fin_tinaja : 'No Registra' }}</strong></p>
                                    </td>
                                    <td>
                                        {{ ($horariosTinaja != '') ? $reserva->cantidad_personas : 'No Registra' }}
                                    </td>
                                    <td>
                                        {{ ($horariosTinaja != '') ? $reserva->cliente->nombre_cliente : 'No Registra' }}
                                    </td>
                                    <td>
                                        <i class='material-icons right {{ $color }}-text'>fiber_manual_record</i>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif





{{-- Botones Vistas --}}
<div class="fixed-action-btn toolbar hide-on-large-only" style="bottom: 45px; right: 24px;">
    <a class="btn-floating btn-large blue">
      <i class="material-icons large">apps</i>
    </a>
    <ul>
            <li class="waves-effect waves-light"><a href="{{ route('backoffice.reserva.index') }}" data-vista="sauna"><i class="material-icons">hot_tub</i></a></li>
            <li class="waves-effect waves-light"><a href="{{ route('backoffice.reserva.index', ['mobileview' => 'masajes']) }}" data-vista="masaje"><i class="material-icons">airline_seat_flat</i></a></li>
            <li class="waves-effect waves-light"><a href="{{ route('backoffice.reserva.index', ['mobileview' => 'ubicacion']) }}" data-vista="ubicacion"><i class="material-icons">location_on</i></a></li>  
    </ul>

</div>