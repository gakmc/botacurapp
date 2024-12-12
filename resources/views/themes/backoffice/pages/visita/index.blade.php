@extends('themes.backoffice.layouts.admin')

@section('title', 'Visitas')

@section('breadcrumbs')
@endsection

@section('head')

@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Visitas</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">


                <div class="card-panel">

                    <div class="row">
                        <div class="card-title">
                            <h4>Hoy</h4>
                        </div>
                        @forelse($reservasHoy as $reserva)
                        @if($reserva->visitas->isNotEmpty())
                        @foreach($reserva->visitas as $visita)
                        {{-- <p>{{ $visita->id }} - {{ $visita->created_at->format('H:i') }}</p> --}}


                        <div class="col s12 m12 l3">
                            <div id="flight-card" class="card">
                                <div class="card-header deep-orange accent-2">
                                    <div class="card-title">
                                        <h4 class="flight-card-title">{{$reserva->cliente->nombre_cliente}}</h4>
                                        <p class="flight-card-date">{{$reserva->fecha_visita}}</p>
                                    </div>
                                </div>
                                <div class="card-content-bg white-text">
                                    <div class="card-content">
                                        <div class="row flight-state-wrapper">
                                            <div class="col s3 m3 l3 center-align">
                                                <div class="flight-state">
                                                    <h4 class="margin">Sauna</h4>
                                                </div>
                                            </div>
                                            <div class="col s1 m1 l1 center-align">
                                                
                                            </div>
                                            <div class="col s3 m3 l3 center-align">
                                                <div class="flight-state">
                                                    <h4 class="margin">Tinaja</h4>
                                                </div>
                                            </div>
                                            <div class="col s1 m1 l1 center-align">
                                                
                                            </div>
                                            <div class="col s3 m3 l3 center-align">
                                                <div class="flight-state">
                                                    <h4 class="margin">Masaje</h4>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col s4 m4 l4 center-align">
                                                <div class="flight-info">
                                                    <p class="small">
                                                        <span class="white-text text-lighten-4">Inicio:</span>{{$visita->horario_sauna}}
                                                    </p>
                                                    <p class="small">
                                                        <span class="white-text text-lighten-4">Fin:</span>{{$visita->hora_fin_sauna}}
                                                    </p>
                                                    
                                                </div>
                                            </div>
                                            <div class="col s4 m4 l4 center-align flight-state-two">
                                                <div class="flight-info">
                                                    <p class="small">
                                                        <span class="white-text text-lighten-4">Inicio:</span>{{$visita->horario_tinaja}}
                                                    </p>
                                                    <p class="small">
                                                        <span class="white-text text-lighten-4">Fin:</span>{{$visita->hora_fin_tinaja}}
                                                    </p>
                                                    
                                                </div>
                                            </div>
                                            <div class="col s4 m4 l4 center-align flight-state-two">
                                                <div class="flight-info">
                                                    <p class="small">
                                                        <span class="white-text text-lighten-4">Inicio:</span>{{$visita->horario_masaje}}
                                                    </p>
                                                    <p class="small">
                                                        <span class="white-text text-lighten-4">Fin:</span>{{$visita->hora_fin_masaje}}
                                                    </p>
                                                    
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                            @else
                            <p>No hay visitas registradas</p>
                            @endif


                        </div>
                        @empty
                        <tr>
                            <td colspan="4">No hay Reservas para hoy</td>
                        </tr>
                        @endforelse

                    </div>
                </div>


                <div class="card-panel">
                    <div class="row">
                        <div class="card-title">
                            <h4>Mañana</h4>
                        </div>

                        @forelse($reservasManana as $reserva)
                        @if($reserva->visitas->isNotEmpty())
                        @foreach($reserva->visitas as $visita)
                        {{-- <p>{{ $visita->id }} - {{ $visita->created_at->format('H:i') }}</p> --}}

                        <div class="col s12 m12 l3">
                            <div id="flight-card" class="card">
                                <div class="card-header deep-orange accent-2">
                                    <div class="card-title">
                                        <h4 class="flight-card-title">{{$reserva->cliente->nombre_cliente}}</h4>
                                        <p class="flight-card-date">{{$reserva->fecha_visita}}, Thu 04:50</p>
                                    </div>
                                </div>
                                <div class="card-content-bg white-text">
                                    <div class="card-content">
                                        <div class="row flight-state-wrapper">
                                            <div class="col s3 m3 l3 center-align">
                                                <div class="flight-state">
                                                    <h4 class="margin">Hora</h4>
                                                    <p class="ultra-small">Sauna</p>
                                                </div>
                                            </div>
                                            <div class="col s1 m1 l1 center-align">
                                                <i class="material-icons flight-icon">local_airport</i>
                                            </div>
                                            <div class="col s3 m3 l3 center-align">
                                                <div class="flight-state">
                                                    <h4 class="margin">Hora</h4>
                                                    <p class="ultra-small">Tinaja</p>
                                                </div>
                                            </div>
                                            <div class="col s1 m1 l1 center-align">
                                                <i class="material-icons flight-icon">local_airport</i>
                                            </div>
                                            <div class="col s3 m3 l3 center-align">
                                                <div class="flight-state">
                                                    <h4 class="margin">SFO</h4>
                                                    <p class="ultra-small">San Francisco</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col s4 m4 l4 center-align">
                                                <div class="flight-info">
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Depart:</span> 04.50
                                                    </p>
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Flight:</span> IB 5784
                                                    </p>
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Terminal:</span> B
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col s4 m4 l4 center-align flight-state-two">
                                                <div class="flight-info">
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Arrive:</span> 08.50
                                                    </p>
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Flight:</span> IB 5784
                                                    </p>
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Terminal:</span> C
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col s4 m4 l4 center-align flight-state-two">
                                                <div class="flight-info">
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Arrive:</span> 08.50
                                                    </p>
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Flight:</span> IB
                                                        5786
                                                    </p>
                                                    <p class="small">
                                                        <span class="grey-text text-lighten-4">Terminal:</span> C
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>



                        </div>

                        @endforeach
                        @else
                        <p>No hay visitas registradas</p>
                        @endif
                        @empty
                        <tr>
                            <td colspan="4">No hay reservas para hoy</td>
                        </tr>
                        @endforelse

                    </div>
                </div>



            </div>
        </div>
    </div>
    @endsection

    @section('foot')
    @endsection