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
                            {{-- {{dd($visita->hora_fin_tinaja)}} --}}
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
    // Agrupar SOLO por horario
    $slots = [];

    // Última visita por reserva (para ubicación/ico trago)
    $ultimaVisitaPorReserva = [];
    foreach ($reservas as $r) {
        foreach ($r->visitas as $v) {
            $ultimaVisitaPorReserva[$v->id_reserva] = $v;
        }
    }

    foreach ($reservas as $r) {
        // definir color por reserva
        if ($r->venta->total_pagar <= 0 && is_null($r->venta->diferencia_programa)) {
            $color = 'orange';
        } elseif ($r->venta->total_pagar <= 0 && !is_null($r->venta->diferencia_programa)) {
            $color = 'green';
        } else {
            $color = 'blue';
        }

        $ultimaVisita = $ultimaVisitaPorReserva[$r->id] ?? null;

        if ($r->masajes->isEmpty()) {
            // si NO tiene masajes, igual mostramos fila “sin registrar”
            $slots['–'][] = [
                'inicio'      => null,
                'fin'         => null,
                'cliente'     => $r->cliente->nombre_cliente,
                'ubicacion'   => optional(optional($ultimaVisita)->ubicacion)->nombre ?? 'No registra',
                'programa'    => $r->programa->nombre_programa,
                'personas'    => [],
                'observacion' => $r->observacion,
                'trago'       => optional($ultimaVisita)->trago_cortesia,
                'color'       => $color,
                'url'         => route('backoffice.reserva.show', $r),
            ];
        } else {
            foreach ($r->masajes as $m) {
                if (!$m->horario_masaje) continue;

                // clave = HORA (sin cliente)
                $keyHora = $m->horario_masaje;

                if (!isset($slots[$keyHora])) $slots[$keyHora] = [];

                // buscar si ya hay un item del mismo cliente en esa hora para agrupar personas
                $pos = null;
                foreach ($slots[$keyHora] as $i => $it) {
                    if ($it['cliente'] === $r->cliente->nombre_cliente) { $pos = $i; break; }
                }

                $fin = (!$r->programa->servicios->contains('nombre_servicio', 'Masaje') && $m->horario_masaje)
                        ? $m->hora_fin_masaje_extra
                        : $m->hora_fin_masaje;

                $itemBase = [
                    'inicio'      => $m->horario_masaje,
                    'fin'         => $fin,
                    'cliente'     => $r->cliente->nombre_cliente,
                    'ubicacion'   => optional(optional($ultimaVisita)->ubicacion)->nombre ?? 'No registra',
                    'programa'    => $r->programa->nombre_programa,
                    'personas'    => [],
                    'observacion' => $r->observacion,
                    'trago'       => optional($ultimaVisita)->trago_cortesia,
                    'color'       => $color,
                    'url'         => route('backoffice.reserva.show', $r),
                ];

                if (is_null($pos)) {
                    $itemBase['personas'][] = $m->persona;
                    $slots[$keyHora][] = $itemBase;
                } else {
                    $slots[$keyHora][$pos]['personas'][] = $m->persona;
                }
            }
        }
    }

    // ordenar por hora (las “–” al final)
    ksort($slots);
@endphp

@foreach($slots as $hora => $items)
    @foreach($items as $row)
        <tr>
            <td class="{{ $row['color'] }}" style="border-radius:5px">
                <strong style="color:#F5F5F5; display:flex; justify-content:center; flex-direction:column">
                    {{ $row['inicio'] ?? '—' }}
                    <i class='tiny material-icons center'>arrow_downward</i>
                    {{ $row['fin'] ?? '—' }}
                </strong>
            </td>
            <td>
                <a href="{{ $row['url'] }}">
                    <strong>{{ $row['cliente'] }} -</strong>
                    {{ $row['ubicacion'] }} -
                    {{ $row['programa'] }} -
                    @if(count($row['personas'])>0)
                        persona {{ implode(' y ', $row['personas']) }} -
                    @else
                        Sin personas asignadas -
                    @endif
                    @if (is_null($row['observacion']))
                        Sin Observaciones
                    @else
                        <strong style="color:#FF4081;">{{ $row['observacion'] }}</strong>
                    @endif
                </a>
            </td>
            @if ($row['trago'] === "Si")
                <td><i class="material-icons" style="color:#FF4081">local_bar</i></td>
            @endif
        </tr>
    @endforeach
@endforeach
</tbody>




                </table>


            </div>
        </div>
    </div>
</div>



