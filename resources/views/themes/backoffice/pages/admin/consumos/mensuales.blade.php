@extends('themes.backoffice.layouts.admin')

@section('title','Consumos')

@section('head')
@endsection

@section('breadcrumbs')
<li>Consumos y Servicios</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Consumos y Servicios Extra</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">
                        <h5><strong>Consumo</strong></h5>


                            <div class="card-content">

                                <span class="card-title">Movimientos recientes</span>
                                <table class="responsive-table">
                                    <thead>
                                        <tr>
                                            <th>Mes</th>
                                            <th>Programas contratados</th>
                                            <th>Total Consumos</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($consumoMensual as $resumen)
                                            @php
                                                $nombreMes = \Carbon\Carbon::createFromDate($resumen->anio, $resumen->mes, 1)
                                                    ->locale('es')->translatedFormat('F Y');
                                            @endphp
                                            <tr> 
                                                <td>{{ ucfirst($nombreMes) }}</td>
                                                <td>{{ $resumen->total_consumos }}</td>
                                                <td>${{ number_format($resumen->subtotales, 0, '', '.') }}</td>
                                                <td>
                                                    <a href="{{ route('backoffice.admin.consumos.detalle', [$resumen->anio, $resumen->mes]) }}" class="btn-small" style="background-color: #039B7B">
                                                        Ver detalle
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                
                                {{ $consumoMensual->links() }}
                            </div>



                    </div>
                </div>

                <div class="card-panel">
                    <div class="row">
                        <h5><strong>Servicios</strong></h5>


                            {{-- <div class="card-content">

                                <span class="card-title">Movimientos recientes</span>
                                <table class="responsive-table">
                                    <thead>
                                        <tr>
                                            <th>Mes</th>
                                            <th>Programas contratados</th>
                                            <th>Abonos</th>
                                            <th>Montos Pendientes</th>
                                            <th>Saldo final</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($estadoMensual as $resumen)
                                            @php
                                                $nombreMes = \Carbon\Carbon::createFromDate($resumen->anio, $resumen->mes, 1)
                                                    ->locale('es')->translatedFormat('F Y');
                                            @endphp
                                            <tr>
                                                <td>{{ ucfirst($nombreMes) }}</td>
                                                <td>{{ $resumen->total_ventas }}</td>
                                                <td>${{ number_format($resumen->total_abonos, 0, ',', '.') }}</td>
                                                <td>${{ number_format($resumen->por_pagar, 0, ',', '.') }}</td>
                                                <td>${{ number_format($resumen->total_abonos+$resumen->por_pagar, 0, ',', '.') }}</td>
                                                <td>
                                                    <a href="{{ route('backoffice.admin.ingresos.detalle', [$resumen->anio, $resumen->mes]) }}" class="btn-small" style="background-color: #039B7B">
                                                        Ver detalle
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                
                                {{ $estadoMensual->links() }}
                            </div> --}}



                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


  
@endsection


@section('foot')




@endsection