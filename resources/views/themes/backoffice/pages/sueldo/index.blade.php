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
                            @forelse ($usuarios as $usuario)
                                @php
                                    $fecha =  \Carbon\Carbon::parse($usuario->dia_trabajado);
                                    $fecha_anio = $fecha->year;
                                    $fecha_mes = $fecha->month;
                                @endphp
                                {{-- <tr>

                                    <td><a href="{{route('backoffice.sueldo.view.admin', ['user'=>$usuario, $fecha_anio, $fecha_mes])}}">{{$usuario->name}}</a></td>
                                    <td>{{ $usuario->sueldos->count()}}</td>
                                    <td>${{ number_format($usuario->sueldos->sum("valor_dia"), 0, '', '.') }}</td>
                                    <td>${{ number_format($usuario->sueldos->sum("sub_sueldo")-$usuario->sueldos->sum("valor_dia"), 0, '', '.') }}</td>
                                    <td>${{ number_format($usuario->sueldos->sum("total_pagar"), 0, '', '.') }}</td>

                                    @php
                                        $sueldoMes += $usuario->sueldos->sum("total_pagar");
                                    @endphp
                                </tr> --}}


                                <tr>
                                    <td >
                                        <strong>
                                            <a href="{{ route('backoffice.sueldo.view.admin', ['user' => $usuario, $anio, $mes]) }}">
                                                {{ $usuario->name }}
                                            </a>
                                        </strong>
                                    </td>
                                </tr>
                                <tr style="background-color: #f0f0f0;">
                                    <td><strong>Semana</strong></td>
                                    <td><strong>Días</strong></td>
                                    <td><strong>Sueldos</strong></td>
                                    <td><strong>Propinas</strong></td>
                                    <td><strong>Total</strong></td>
                                </tr>
                                @php
                                    $sueldoBruto = 0;
                                    $totalUsuario = 0;
                                @endphp
                                @foreach($usuario->totales_semanales as $semana => $datos)
                                {{-- {{dd($datos)}} --}}
                                    <tr>
                                        <td>{{ $semana }}</td>
                                        <td>{{ $datos['dias'] }}</td>
                                        <td>${{ number_format($datos['sueldos'], 0, '', '.') }}</td>
                                        <td>${{ number_format($datos['propinas'], 0, '', '.') }}</td>
                                        <td>${{ number_format($datos['total'], 0, '', '.') }}</td>
                                    </tr>
                                    @php 
                                        $sueldoBruto += $datos['sueldos'];
                                        $totalUsuario += $datos['total'];
                                    @endphp
                                @endforeach
                                <tr>
                                    <td colspan="4" class="right-align"><strong>Total mensual</strong></td>
                                    <td><strong>${{ number_format($totalUsuario, 0, '', '.') }}</strong></td>
                                    @php
                                        $sueldoMes += $totalUsuario;
                                        $totalSueldoBruto += $sueldoBruto;
                                    @endphp
                                </tr>
                                <tr><td colspan="5"><div class="divider"></div></td></tr>

                            @empty
                                <tr>
                                    <td colspan="4">No hay registros para este período.</td>
                                </tr>
                            @endforelse

                            <tr>
                                <td colspan="3">  </td>
                                <td><strong>Total sueldos: ${{number_format($totalSueldoBruto,0,'','.')}} </strong></td>
                                <td><strong>Total a pagar: ${{number_format($sueldoMes,0,'','.')}} </strong></td>
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
