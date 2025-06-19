@extends('themes.backoffice.layouts.admin')

@section('title','Remuneraciones')

@section('head')
@endsection

@section('breadcrumbs')
{{-- <li><a href="{{route('backoffice.admin.ingresos')}}">Ingresos detallados</a></li> --}}
<li>Remuneraciones <strong>{{--$fecha->locale('es')->isoFormat('DD [de] MMMM [de] YYYY')--}}</strong></li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')
<div class="section">
    <p class="caption"><strong>Remuneraciones</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <h4 class="header2">Remuneraciones mes de <strong>{{ ucfirst(\Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM')) }}</strong></h4>

                    {{-- Formulario para seleccionar mes y año --}}
                    {{-- <form method="GET" action="{{ route('backoffice.sueldos.index') }}"> --}}

                            <div class="row">
                                {{-- <div class="input-field col s12 m3 offset-m2">
                                    <label for="mes">Mes:</label>
                                    <select name="mes" id="mes">
                                        @foreach (range(1, 12) as $month)
                                            <option value="{{ $month }}" {{ $mes == $month ? 'selected' : '' }}>
                                                {{ ucfirst(\Carbon\Carbon::create()->month($month)->locale('es')->isoFormat('MMMM')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            
                                <div class="input-field col s12 m3">
                                    
                                    <label for="anio">Año:</label>
                                    <select name="anio" id="anio">
                                        @foreach (range(now()->year - 2, now()->year) as $year)
                                        <option value="{{ $year }}" {{ $anio == $year ? 'selected' : '' }}>
                                            {{ $year }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div> --}}

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


                            {{-- <div class="input-field col s12 m3">
                                    <button type="submit" class="btn">Filtrar</button>
                                </div>
                             --}}
                        </div>
                    {{-- </form> --}}

                    {{-- Tabla de sueldos --}}
                    @php
                        $sueldoMes = 0;
                        $totalSueldoBruto = 0;
                    @endphp
                    <table class="centered">
                        {{-- <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Dias</th>
                                <th>Sueldos</th>
                                <th>Total Propinas</th>
                                <th>Total a Pagar</th>
                            </tr>
                        </thead> --}}
                        <tbody>
@forelse ($semanas as $rango => $usuariosSemana)
    <h5><strong>{{ $rango }}</strong></h5>
    <table class="">
        <thead>
            <tr>
                <th>Funcionario</th>
                <th>Días</th>
                <th>Sueldos</th>
                <th>Propinas</th>
                <th>Total</th>
                <th>Pagar</th>
            </tr>
        </thead>
        <tbody>
            @php $totalSemana = 0; @endphp
            @foreach ($usuariosSemana as $usuario)
                <tr>
                    <td style="width: 264.22px;">
                        <a href="{{ route('backoffice.sueldo.view.admin', ['user' => $usuario['user_id'], $anio, $mes]) }}">
                            {{ $usuario['name'] }}
                        </a>
                    </td>
                    <td>{{ $usuario['dias'] }}</td>
                    <td>${{ number_format($usuario['sueldos'], 0, '', '.') }}</td>
                    @php
                        $totalSueldoBruto += $usuario['sueldos'];
                    @endphp
                    <td>${{ number_format($usuario['propinas'], 0, '', '.') }}</td>
                    <td>${{ number_format($usuario['total'], 0, '', '.') }}</td>
                    @php
                        $sueldoMes += $usuario['total'];
                    @endphp
                    <td></td>
                </tr>
                @php 
                    $totalSemana += $usuario['total'];
                @endphp
            @endforeach
            <tr>
                <td colspan="4" class="right-align"><strong>Total semana</strong></td>
                <td><strong>${{ number_format($totalSemana, 0, '', '.') }}</strong></td>
            </tr>
        </tbody>
    </table>
    {{-- <div class="divider"></div> --}}
    @empty
    <p>No hay registros para este período.</p>
    @endforelse
    
    <table>
        <tbody>
            <tr>
                <td ></td>
                <td ></td>
                <td ></td>
                <td class="center"><strong>Total sueldos: ${{number_format($totalSueldoBruto,0,'','.')}} </strong></td>
                <td class="right"><strong>Total a pagar: ${{number_format($sueldoMes,0,'','.')}} </strong></td>
            </tr>
        </tbody>
    </table>
                        
                    

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


    <script>
        function cambiarMesAnio(valor) {
            const [mes, anio] = valor.split('-');
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = "{{ route('backoffice.sueldos.index') }}";

            const inputMes = document.createElement('input');
            inputMes.name = 'mes';
            inputMes.value = mes;
            form.appendChild(inputMes);

            const inputAnio = document.createElement('input');
            inputAnio.name = 'anio';
            inputAnio.value = anio;
            form.appendChild(inputAnio);

            document.body.appendChild(form);
            form.submit();
        }
    </script>
    

@endsection
