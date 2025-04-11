@extends('themes.backoffice.layouts.admin')

@section('title','Finanzas')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.admin.ingresos')}}">Ingresos</a></li>
<li>Detalles</li>
@endsection


@section('dropdown_settings')
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Ingresos</strong></p>
    <div class="divider"></div>
    <div id="basic-form" class="section">
        <div class="row">
            <div class="col s12 ">
                <div class="card-panel">
                    <div class="row">


                        <h5><strong>Detalle de ingresos: {{ ucfirst($nombreMes) }}</strong></h5>
                        <table>
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Abono</th>
                                    <th>Diferencia</th>
                                    <th>Pendiente</th>
                                    <th>Total pagar</th>
                                    <!-- Agrega más columnas si las necesitas -->
                                </tr>
                            </thead>

                                <tbody>
                                    @foreach ($ventasAgrupadas as $venta)
                                        <tr>
                                            <td><a href="{{route('backoffice.admin.ingresos.detalleDia', [$anio, $mes, $venta->dia])}}">{{ \Carbon\Carbon::parse($venta->fecha)->format('d-m-Y') }}</a></td>
                                            <td class="green-text">${{ number_format($venta->abono, 0, ',', '.') }}</td>
                                            @if ($venta->diferencia == null)
                                                <td>Por pagar</td>
                                            @else
                                                <td class="green-text">${{ number_format($venta->total_pagar-$venta->abono, 0, ',', '.') }}</td>
                                                {{-- CONSIDERAR RESTAR TOTAL PAGAR DE ABONO --}}
                                            @endif
                                            <td @if ($venta->pendiente > 0) class="red-text" @endif>
                                                ${{ number_format($venta->pendiente, 0, ',', '.') }}
                                            </td>
                                            <td>${{ number_format($venta->total_pagar, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                
                                
                
                        </table>
                        
                        <div class="center">
                            {{ $ventasAgrupadas->links('vendor.pagination.materialize') }}
                        </div>
                    </div>
                </div>


                <div class="card-panel">
                        <div class="row">
                            {{-- <div class="col s6">
                                <h5><strong>Resumen de ingresos mensual</strong></h5>
                                <div class="collection">
                                    <a href="#!" class="collection-item"><span class="badge green white-text">${{number_format($ingresosVentas,0,'','.')}}</span>Ingresos Totales</a>
                                </div>
                                <div class="collection">
                                    <a href="#!" class="collection-item"><span class="badge red white-text">${{number_format($ventasPendientes,0,'','.')}}</span>Saldo Pendiente</a>
                                </div>
                            </div> --}}
                            <div class="col s6">
                                <h5><strong>Resumen de transacciones</strong></h5>
                                <table>
                                    <thead>
                                      <tr>
                                          <th>Tipo Transaccion</th>
                                          <th>Abono</th>
                                          <th>Diferencia</th>
                                      </tr>
                                    </thead>
                            
                                    <tbody>
                                        @foreach ($tiposTransacciones as $tipo)
                                            @php
                                                
                                            @endphp
                                            <tr>
                                                <td>{{$tipo->nombre}}</td>
                                                <td>{{ $tipo->total_abonos }}</td>
                                                <td>{{ $tipo->total_diferencias }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>


                            <div class="col s6">
                                <h5><strong>Resumen de programas</strong></h5>
                                <table>
                                    <thead>
                                      <tr>
                                          <th>Programa</th>
                                          <th>Contratado</th>
                                      </tr>
                                    </thead>
                            
                                    <tbody>
                                        @foreach ($programas as $programa)
                                            @php
                                                // dd($programa->count());
                                            @endphp
                                            <tr>
                                                <td>{{$programa->nombre_programa}}</td>
                                                <td>
                                                    {{ $programa->total_programas }}
                                                    @if ($programa->total_programas == 0 || $programa->total_programas > 1)
                                                        veces
                                                    @else
                                                        vez
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                </div>





            </div>
        </div>
    </div>
</div>


  
@endsection


@section('foot')




@endsection