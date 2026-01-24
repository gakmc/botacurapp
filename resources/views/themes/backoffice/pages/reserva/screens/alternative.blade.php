<div class="col s12">
    <h5>Horarios: {{ \Carbon\Carbon::parse($fecha)->format('d-m-Y') }}</h5>
    <div class="row">

        @foreach($reservas as $reserva)
        
        @php

                $horariosSauna = $reserva->visitas->pluck('horario_sauna')->filter()->unique()->join(', '); // Obtener horarios de sauna
                $horariosTinaja = $reserva->visitas->pluck('horario_tinaja')->filter()->unique()->join(', '); // Obtener horarios de tinaja
                $ubicacion = [];
                
                foreach ($reserva->visitas->sortBy('ubicacion.id') as $index=>$visita) {

                    $ubicacion[] = $visita->ubicacion->nombre ?? 'No registra';
                    if ($reserva->masajes->isEmpty()) {
                        $horariosMasaje = null;
                    }else {
                        $horariosMasaje = $reserva->masajes->pluck('horario_masaje')->filter()->unique()->join(', '); // Obtener horarios de masaje
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



                                        <p><strong>Sauna: </strong>{{ ($horariosSauna != '') ? $horariosSauna :  'No Registra Sauna'}} </p>
                                        <p><strong>Tinaja: </strong>{{ ($horariosTinaja != '') ? $horariosTinaja : 'No Registra Tinaja' }} </p>
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