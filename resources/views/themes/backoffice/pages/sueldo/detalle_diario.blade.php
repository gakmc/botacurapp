@extends('themes.backoffice.layouts.admin')

@section('title','Detalle Diario')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.admin.ingresos')}}">Ingresos detallados</a></li> --}}
<li><a href="{{ route('backoffice.sueldo.view.admin',[$user,$fecha->year, $fecha->month]) }}">Pagos del Mes</a></li>
<li>Detalle: <strong>{{$fecha->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')}}</strong></li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')
{{-- <div class="section">
    <p class="caption"><strong>Detalle Diario</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <h4 class="header2">Detalle sueldo <strong>{{$user->name}}</strong>, Dia: <strong>{{$fecha->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')}}</strong></h4>


                    @php
                        $sueldoMes = 0;
                    @endphp
                    <table class="centered">
                        <thead>
                            <tr>
                                <th>Valor Día</th>
                                <th>Sub Sueldo</th>
                                <th>Total a Pagar</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>

                    


                </div>
            </div>
        </div>
    </div>
</div> --}}


<div class="section">
    <div class="row">
        <div class="col s12 m10 offset-m1">
            <div class="card">
                <div class="card-content">
                    <div class="center-align">
                        <img src="/images/logo/logo.png" alt="materialize logo" >
                        <p>Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana</p>
                    </div>
                    <br>

                    <h5 class="center-align">Detalle del Día</h5>
                    <h6 class="center-align grey-text text-darken-1">
                        {{$fecha->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY')}}
                    </h6>

                    <div class="divider"></div>
                    <br>

                    <div class="row">
                        <div class="col s12 m4">
                            <div class="card-panel teal lighten-4 center-align">
                                <h6 class="teal-text text-darken-4">Valor del Día</h6>
                                <h5 class="black-text">${{ number_format($sueldo->valor_dia, 0, '', '.')}}</h5>
                            </div>
                        </div>
                        <div class="col s12 m4">
                            <div class="card-panel amber lighten-4 center-align">
                                <h6 class="amber-text text-darken-4">Propinas</h6>
                                <h5 class="black-text">${{number_format($total_propina_usuario, 0, '', '.')}}</h5>
                            </div>
                        </div>
                        <div class="col s12 m4">
                            <div class="card-panel green lighten-4 center-align">
                                <h6 class="green-text text-darken-4">Total a Pagar</h6>
                                <h5 class="black-text">${{ number_format($sueldo->total_pagar, 0, '', '.') }}</h5>
                            </div>
                        </div>
                    </div>

                    <h5 class="center-align">Detalle de Propinas del Día</h5>

                    @if(count($asignaciones) > 0)
                        <table class="striped centered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Reserva</th>
                                    <th>Monto Asignado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($asignaciones as $index => $asignacion)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $asignacion->nombre_cliente }}</td>
                                        <td>${{ number_format($asignacion->monto_asignado, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    

                    @else
                        <div class="card-panel grey lighten-3 center-align">
                            <span class="grey-text text-darken-1">No se asignaron propinas a este usuario en esta fecha.</span>
                        </div>
                    @endif
                    
                    

                    <div class="center-align">
                        <a href="{{ route('backoffice.sueldo.view', $user) }}" class="btn waves-effect teal darken-2">
                            <i class="material-icons left">arrow_back</i> Volver al Estado de Cuenta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('foot')

@endsection
