@extends('themes.backoffice.layouts.admin')

@section('title','Remuneraciones')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{ route('backoffice.sueldos.index') }}">Remuneraciones</a></li>
<li><strong>{{$user->name}}</strong></li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Remuneraciones {{$user->name}}</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    
                    <div class="row">
                        <div class="col s12 center-align" style="height: 120px">
                            <img src="/images/logo/logo.png" alt="logo" style="height: 120px">
                            <p style="margin-top: 0; margin-bottom: 20px;">
                                Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana
                            </p>
                        </div>
                        <div class="col s12 center-align">
                            <h4 class="header2" style="margin-top: 50px;">Remuneraciones <strong>{{ $user->name }}</strong></h4>
                        </div>
                    </div>
                


                    <div class="row">

                        <div class="input-field col s12 m6 offset-m3">
                            
                            <select name="mes_anio" id="mes_anio" onchange="cambiarMesAnio(this.value)">
                                @foreach($fechasDisponibles as $fecha)
                                    @php
                                        $value = $fecha->mes . '-' . $fecha->anio;
                                        $mesNombre = ucfirst(\Carbon\Carbon::create()->month($fecha->mes)->locale('es')->isoFormat('MMMM'));
                                    @endphp
                                    <option value="{{ $value }}" {{ $mes . '-' . $anio == $value ? 'selected' : '' }}>
                                        {{ $mesNombre }} {{ $fecha->anio }}
                                    </option>
                                @endforeach
                            </select>
                            <label for="mes_anio">Selecciona Mes y Año</label>
                        </div>

                    </div>



                    <div class="row">
                        <div class="col s12">
                            {{-- Tabla de sueldos --}}
                            @php
                                $sueldoMes = 0;
                                $diasTrabajados = 0;
                            @endphp
                            <table class="centered">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cant. Masajes</th>
                                        <th>Total Masajes</th>
                                        <th>Total a Pagar</th>
                                    </tr>
                                </thead>

                                <tbody>
@if ($sueldosAgrupados->isNotEmpty())
    @php
        $sueldoMes = 0;
        $diasTrabajados = 0;
    @endphp
    @foreach($sueldosAgrupados as $semana => $sueldosSemana)
        <tr style="background-color: #f2f2f2;">
            <td colspan=""><strong>Semana: {{ $semana }}</strong></td>
        </tr>
        @foreach($sueldosSemana as $sueldo)
            @php
                $fecha = \Carbon\Carbon::parse($sueldo->dia_trabajado);

                $diaClave = \Carbon\Carbon::parse($sueldo->dia_trabajado)->toDateString();

                 $cantidadMasajes = $masajesPorDia[$diaClave] ?? 0;

                $dia = $fecha->day;
                $sueldoMes += $sueldo->total_pagar;
                $diasTrabajados++;
                $dias = $diasTrabajados > 1 ? 'días' : 'día';
            @endphp
            <tr>
                <td><a href="{{ route('backoffice.sueldo.view.diario', ['user' => $user, $anio, $mes, $dia]) }}">
                    {{ $fecha->locale('es')->isoFormat('ddd D [de] MMM') }}
                </a></td>
                <td>{{ $cantidadMasajes }}</td>
                <td>${{ number_format($sueldo->total_pagar, 0, '', '.') }}</td>
                <td>${{ number_format($sueldo->total_pagar, 0, '', '.') }}</td>
            </tr>
        @endforeach
    @endforeach
    <tr>
        <td colspan="2">  </td>
        <td><strong>Días Trabajados: {{ $diasTrabajados }} {{ $dias }}</strong></td>
        <td><strong>Total del mes: ${{ number_format($sueldoMes, 0, '', '.') }} </strong></td>
    </tr>
@else
    <tr>
        <td colspan="4">No hay registros para este período.</td>
    </tr>
@endif
</tbody>
                            </table>
                        </div>
                    </div>
                    

                    {{-- Paginación --}}
                    {{-- <div class="center-align">
                        {{ $sueldos->appends(['mes' => $mes, 'anio' => $anio])->links('vendor.pagination.materialize') }}
                    </div> --}}

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('foot')
    <script>
        $(document).ready(function () {
            $('select').material_select({
                classes:"left-text"
            });
        });
    </script>

    {{-- <script>
        function cambiarMesAnio(valor) {
            const [mes, anio] = valor.split('-');
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = `/sueldos/{{ $user->id }}/${anio}/${mes}`;
            document.body.appendChild(form);
            form.submit();
        }
    </script> --}}

    <script>
        function cambiarMesAnio(valor) {
            const [mes, anio] = valor.split('-');
            const ruta = "{{ route('backoffice.sueldo.view.admin', ['user' => $user->id, 'anio' => '__ANIO__', 'mes' => '__MES__']) }}"
                    .replace('__ANIO__', anio)
                    .replace('__MES__', mes);
            window.location.href = ruta;
        }
    </script>

@endsection
