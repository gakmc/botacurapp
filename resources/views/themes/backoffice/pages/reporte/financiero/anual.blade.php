@extends('themes.backoffice.layouts.admin')

@section('content')
<div class="section">
              <p class="caption">Resumen Anual.</p>
              <div class="divider"></div>
              <div id="basic-form" class="section">
                <div class="row">
                  <div class="col s12 m10 offset-m1 ">
                    <div class="card-panel">

<div class="container">
    <h4>Resumen Anual de Finanzas {{$anio}}</h4>
    <table class="highlight">
        <thead>
            <tr>
                <th>Mes</th>
                <th>Ingresos</th>
                <th>Egresos</th>
                <th>Ganancias Percibidas</th>
            </tr>
        </thead>
        <tbody>
            @php
                $ingresoAnual = 0;
                $egresoAnual = 0;
                $totalAnual = 0;
            @endphp
            @for ($i = 1; $i <= 12; $i++)
                @php
                    $ingresos = 
                        optional($abonos->firstWhere('mes', $i))->total +
                        optional($diferencias->firstWhere('mes', $i))->total +
                        optional($consumos->firstWhere('mes', $i))->total +
                        optional($ventasDirectas->firstWhere('mes', $i))->total +
                        optional($servicios->firstWhere('mes', $i))->total;

                    $egresosData = 
                        optional($egresos->firstWhere('mes', $i))->total +
                        optional($sueldos->firstWhere('mes', $i))->total +
                        optional($bonos->firstWhere('mes', $i))->total +
                        optional($impuestos->firstWhere('mes', $i))->total;


                    $total = $ingresos - $egresosData;

                    $ingresoAnual += $ingresos;
                    $egresoAnual += $egresosData;
                    $totalAnual += $total;

                @endphp

                <tr>
                    {{-- <td>{{ DateTime::createFromFormat('!m', $i)->format('F') }}</td> --}}
                    <td><a href="{{route('backoffice.finanzas.resumen.mensual',[$anio,$i])}}">{{ucfirst(\Carbon\Carbon::create()->month($i)->locale('es')->isoFormat('MMMM'))}}</a></td>
                    <td class="green-text">${{ number_format($ingresos, 0, ',', '.') }}</td>
                    <td class="red-text">${{ number_format($egresosData, 0, ',', '.') }}</td>
                    <td><strong>${{ number_format($total, 0, ',', '.') }}</strong></td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td><strong>Ingreso Anual: ${{number_format($ingresoAnual,0,'','.')}}</strong></td>
                <td><strong>Egreso Anual: ${{number_format($egresoAnual,0,'','.')}}</strong></td>
                <td><strong>Total Anual: ${{number_format($totalAnual,0,'','.')}}</strong></td>
            </tr>
        </tfoot>
    </table>


</div>
</div>
</div>
</div>
</div>
@endsection
