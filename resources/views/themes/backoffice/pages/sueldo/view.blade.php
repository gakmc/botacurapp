@extends('themes.backoffice.layouts.admin')

@section('content')
<div class="section">
    <p class="caption"><strong>Estado de Cuenta</strong></p>
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
                            <h4 class="header2" style="margin-top: 50px;">Estado de cuenta <strong>{{ $user->name
                                    }}</strong></h4>
                        </div>
                    </div>



                    <div class="row">

                        <div class="input-field col s12 m6 offset-m3">

                            <select name="mes_anio" id="mes_anio" onchange="cambiarMesAnio(this.value)">
                                @foreach($fechasDisponibles as $fecha)
                                @php
                                $value = $fecha->mes . '-' . $fecha->anio;
                                $mesNombre =
                                ucfirst(\Carbon\Carbon::create()->month($fecha->mes)->locale('es')->isoFormat('MMMM'));
                                @endphp
                                <option value="{{ $value }}" {{ $mes . '-' . $anio==$value ? 'selected' : '' }}>
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
                                        <th>Semana</th>
                                        <th>Dias Trabajados</th>
                                        <th>Sueldos</th>
                                        <th>Propinas</th>
                                        <th>Bono</th>
                                        <th>Motivo</th>
                                        <th>Total a Pagar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if ($sueldosAgrupados->isNotEmpty())
                                    @foreach($sueldosAgrupados as $rangoSemana => $sueldos)
                                    <tr>
                                        <td>{{ $rangoSemana }}</td>
                                        @php
                                        foreach ($sueldos as $index => $sueldo){
                                        $diasTrabajados = $index+1;
                                        }
                                        @endphp
                                        <td>{{ $diasTrabajados }}</td>
                                        <td>${{ number_format($sueldos->sum('valor_dia'), 0, '', '.') }}</td>
                                        <td>${{ number_format($sueldos->sum('total_pagar') - $sueldos->sum('valor_dia'),
                                            0, '', '.') }}</td>
                                        <td>${{ number_format($sueldos->sum('bono'), 0, '', '.') }}</td>
                                        <td>{{ $sueldos->first()->motivo ?? '-' }}</td>


                                        <td>${{ number_format($sueldos->sum('total_pagar')+$sueldos->sum('bono'), 0, '', '.') }}</td>
                                        @php
                                        $sueldoMes+=$sueldos->sum('total_pagar');
                                        $sueldoMes+=$sueldos->sum('bono');
                                        @endphp
                                        {{-- @foreach($sueldos as $sueldo)
                                        <li>{{ $sueldo->dia_trabajado }} - ${{ number_format($sueldo->total_pagar, 0,
                                            ',', '.') }}</li>
                                        @endforeach --}}


                                    </tr>
                                    @endforeach
                                    @else
                                    <tr>
                                        <td colspan="4">No hay registros para este período.</td>
                                    </tr>
                                    @endif
                                    <tr>
                                        <td colspan="4"> </td>
                                        <td><strong>Total del mes: ${{number_format($sueldoMes,0,'','.')}} </strong>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    {{-- Paginación --}}
                    {{-- <div class="center-align">
                        {{ $sueldos->appends(['mes' => $mes, 'anio' => $anio])->links('vendor.pagination.materialize')
                        }}
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