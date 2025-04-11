@extends('themes.backoffice.layouts.admin')

@section('content')
<div class="section">
    <p class="caption"><strong>Estado de Cuenta</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12">
                <div class="card-panel">
                    <h4 class="header2">Estado de cuenta <strong>{{$user->name}}</strong></h4>

                    {{-- Formulario para seleccionar mes y año --}}
                    {{-- <form method="GET" action="{{ route('backoffice.sueldo.view', Auth::user()) }}"> --}}

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
                                </div> --}}
                            </div>

                    {{-- </form> --}}

                    {{-- Tabla de sueldos --}}
                    @php
                        $sueldoMes = 0;
                    @endphp
                    <table class="centered">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Valor Día</th>
                                <th>Propinas</th>
                                <th>Total a Pagar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sueldos as $sueldo)
                                @php
                                    $fecha =  \Carbon\Carbon::parse($sueldo->dia_trabajado);
                                    $fecha_anio = $fecha->year;
                                    $fecha_mes = $fecha->month;
                                    $fecha_dia = $fecha->day;
                                @endphp
                                <tr>
                                    <td><a href="{{route('backoffice.sueldo.view.diario', ['user'=>Auth::user(), $fecha_anio, $fecha_mes, $fecha_dia])}}">{{ \Carbon\Carbon::parse($sueldo->dia_trabajado)->locale('es')->isoFormat('D [de] MMM') }}</a></td>
                                    <td>${{ number_format($sueldo->valor_dia, 0, '', '.') }}</td>
                                    <td>${{ number_format($sueldo->sub_sueldo-$sueldo->valor_dia, 0, '', '.') }}</td>
                                    <td>${{ number_format($sueldo->total_pagar, 0, '', '.') }}</td>
                                </tr>
                                @php
                                $sueldoMes += $sueldo->total_pagar
                                @endphp
                            @empty
                                <tr>
                                    <td colspan="4">No hay registros para este período.</td>
                                </tr>
                            @endforelse
                            <tr>
                                <td colspan="3">  </td>
                                <td><strong>Total del mes: ${{number_format($sueldoMes,0,'','.')}} </strong></td>
                            </tr>
                        </tbody>
                    </table>

                    

                    {{-- Paginación --}}
                    <div class="center-align">
                        {{ $sueldos->appends(['mes' => $mes, 'anio' => $anio])->links('vendor.pagination.materialize') }}
                    </div>
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
            form.action = "{{ route('backoffice.sueldo.view', $user) }}";

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
