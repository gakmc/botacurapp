@extends('themes.backoffice.layouts.admin')

@section('title')
Resumen {{ucfirst(\Carbon\Carbon::create()->month($mes)->locale('es')->isoFormat('MMMM'))}}
@endsection

@section('content')
<div class="section">
              <p class="caption">Resumen Mensual - {{ ucfirst($mesNombre) }} {{ $anio }}.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m10 offset-m1 ">
                    <div class="card-panel">
<div class="container">
    <h4 class="center-align">Reportes por Semana</h4>

    @php
        // Agrupar por semana (yearweek)
        $semanas = collect($abonos)->pluck('yearweek')
            ->merge(collect($diferencias)->pluck('yearweek'))
            ->merge(collect($consumos)->pluck('yearweek'))
            ->merge(collect($servicios)->pluck('yearweek'))
            ->merge(collect($egresos)->pluck('yearweek'))
            ->merge(collect($sueldos)->pluck('yearweek'))
            ->merge(collect($bonos)->pluck('yearweek'))
            ->merge(collect($impuestos)->pluck('yearweek'))
            ->merge(collect($ventasDirectas)->pluck('yearweek'))
            ->unique()->sort();
    @endphp

    @php
        $ingresoMesData = 0;
        $egresoMesData = 0;
        $totalMesData = 0;
    @endphp

    @foreach($semanas as $week)
        @php
            // Obtener fechas dentro de esa semana
            $fechas = collect($abonos)
                ->merge($diferencias)
                ->merge($consumos)
                ->merge($servicios)
                ->merge($egresos)
                ->merge($sueldos)
                ->merge($bonos)
                ->merge($impuestos)
                ->merge($ventasDirectas)
                ->where('yearweek', $week)
                ->pluck('fecha')
                ->map(function($f) {
                    return \Carbon\Carbon::parse($f)->toDateString(); // <-- aquí
                })
                ->unique()
                ->sort();


                $fechaCarbon = \Carbon\Carbon::parse($fechas->first());
                $inicio = $fechaCarbon->startOfWeek()->translatedFormat('d M');
                $fin    = $fechaCarbon->endOfWeek()->translatedFormat('d M');
        @endphp

                @php
                    $ingresosData = 0;
                    $egresosData = 0;
                @endphp

        <h5 class="teal-text">Semana {{ $inicio }} - {{ $fin }}</h5>

        <table class="striped responsive-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Abonos</th>
                    <th>Diferencias</th>
                    <th>Consumos</th>
                    <th>Servicios</th>
                    <th>Sueldos</th>
                    <th>Bonos</th>
                    <th>Egresos</th>
                    <th>Impuestos</th>
                </tr>
            </thead>
            <tbody>


                @foreach($fechas as $fecha)

                @php
                    $ingresosData += (optional($abonos->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($diferencias->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($consumos->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($ventasDirectas->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($servicios->firstWhere('fecha', $fecha))->total ?? 0);

                    $egresosData += (optional($egresos->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($sueldos->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($bonos->firstWhere('fecha', $fecha))->total ?? 0)
                                + (optional($impuestos->firstWhere('fecha', $fecha))->total ?? 0);
                @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</td>
                        <td>${{ number_format(optional($abonos->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($diferencias->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($consumos->firstWhere('fecha', $fecha))->total + optional($ventasDirectas->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($servicios->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($sueldos->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($bonos->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($egresos->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                        <td>${{ number_format(optional($impuestos->firstWhere('fecha', $fecha))->total ?? 0, 0, '', '.') }}</td>
                    </tr>
                @endforeach
                @php
                   $ingresoMesData += $ingresosData;
                   $egresoMesData += $egresosData;
                   $totalMesData += $ingresosData - $egresosData;
                @endphp
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5"></td>
                    <td><strong>Ingresos: ${{ number_format($ingresosData, 0, ',', '.') }}</strong></td>
                    <td><strong>Egresos: ${{ number_format($egresosData, 0, ',', '.') }}</strong></td>
                    <td><strong>Total: ${{ number_format($ingresosData - $egresosData, 0, ',', '.') }}</strong></td>
                </tr>
            </tfoot>
        </table>
        <br>

    @endforeach
</div>


            <h5 class="center-align">Total Mensual:</h5>

                <div class="row">
                  <div class="col s10 m3 offset-m1">
                    <div class="card-panel blue gradient-shadow center">
                      <span class="white-text"><strong>Ingresos: </strong>${{ number_format($ingresoMesData, 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel blue gradient-shadow center">
                      <span class="white-text"><strong>Egresos: </strong>${{ number_format($egresoMesData, 0, ',', '.') }}</span>
                    </div>
                  </div>
                  <div class="col s10 m3">
                    <div class="card-panel blue gradient-shadow center">
                      <span class="white-text"><strong>Total: </strong>${{ number_format($totalMesData, 0, ',', '.') }}</span>
                    </div>
                  </div>


                </div>

                    </div>
                </div>
                </div>
                </div>
@endsection