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
                    <form method="GET" action="{{ route('backoffice.sueldo.view', Auth::user()) }}">

                        <div class="row">
                            <div class="input-field col s12 m3 offset-m2">
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
                                </div>
                                <div class="input-field col s12 m3">
                            
                                <button type="submit" class="btn">Filtrar</button>
                                </div>
                            </div>

                    </form>

                    {{-- Tabla de sueldos --}}
                    @php
                        $sueldoMes = 0;
                    @endphp
                    <table class="centered">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Valor Día</th>
                                <th>Sub Sueldo</th>
                                <th>Total a Pagar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($sueldos as $sueldo)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($sueldo->dia_trabajado)->locale('es')->isoFormat('D [de] MMM') }}</td>
                                    <td>${{ number_format($sueldo->valor_dia, 0, ',', '.') }}</td>
                                    <td>${{ number_format($sueldo->sub_sueldo, 0, ',', '.') }}</td>
                                    <td>${{ number_format($sueldo->total_pagar, 0, ',', '.') }}</td>
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
@endsection
