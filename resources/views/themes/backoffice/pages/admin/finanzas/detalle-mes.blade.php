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
                                    <th>Consumo</th>
                                    <th>Servicios Extra</th>
                                    <th>Total pagado</th>
                                    <!-- Agrega más columnas si las necesitas -->
                                </tr>
                            </thead>

                                <tbody>
                                    @foreach ($ventasAgrupadas as $venta)
                                    @php
                                        $consumoSinPropina = 0;
                                        $serviciosExtras = 0;

                                        // dd($venta);

                                        // if ($venta->consumo != null)
                                        // {
                                        //     $consumoSinPropina = $venta->consumo->detallesConsumos->sum("subtotal");  
                                        //     $serviciosExtras = $venta->consumo->detalleServiciosExtra->sum("subtotal");
                                            
                                        // }

                                        if (!empty($venta->consumo)) {
                                            foreach ($venta->consumo as $consumo) {
                                                $consumoSinPropina += $consumo->detallesConsumos->sum('subtotal');
                                                $serviciosExtras += $consumo->detalleServiciosExtra->sum('subtotal');
                                            }
                                        }
                                    @endphp
                                        <tr>
                                            <td>
                                                <a href="{{route('backoffice.admin.ingresos.detalleDia', [$anio, $mes, $venta->dia])}}">
                                                    {{ \Carbon\Carbon::parse($venta->fecha)->format('d-m-Y') }}
                                                </a>
                                            </td>
                                            <td class="green-text">
                                                ${{ number_format($venta->abono, 0, ',', '.') }}
                                            </td>
                                            
                                            @if ($venta->diferencia == null && $venta->pendiente > 0)
                                                <td>Por pagar</td>
                                            @else
                                                <td class="green-text">${{ number_format($venta->diferencia, 0, ',', '.') }}</td>
                                                {{-- CONSIDERAR RESTAR TOTAL PAGAR DE ABONO --}}
                                            @endif


                                            <td @if ($venta->pendiente > 0) class="red-text" @endif>
                                                ${{ number_format($venta->pendiente, 0, ',', '.') }}
                                            </td>
                                            <td>
                                                ${{ number_format($consumoSinPropina, 0, ',', '.') }}
                                            </td>
                                            <td>
                                                ${{ number_format($serviciosExtras, 0, ',', '.') }}
                                            </td>
                                            <td>${{ number_format($venta->abono + $venta->diferencia + $consumoSinPropina + $serviciosExtras, 0, ',', '.') }}</td>
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
                            
                            
                            <div class="col s12 m3">
                                <h5><strong>Resumen de programas</strong></h5>
                                <table class="striped centered">
                                    <thead>
                                      <tr>
                                          <th class="white-text" style="background-color: #039B7B;">Programa</th>
                                          {{-- <th>Contratado</th> --}}
                                      </tr>
                                    </thead>
                            
                                    <tbody>
                                        @foreach ($programas as $programa)
                                            @php
                                                // dd($programa->count());
                                                $contratado = ($programa->total_programas == 0 || $programa->total_programas > 1) ? 'contratados' : 'contratado';
                                            @endphp
                                            <tr>
                                                <td>{{$programa->nombre_programa}}: {{$programa->total_programas }} {{$contratado}}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    
                                </table>
                            </div>



                            
                            
                            <div class="col s12 m3">
                                <h5><strong>Resumen de transacciones</strong></h5>
                                <table class="striped bordered">
                                    <thead>
                                      <tr>
                                          <th class="white-text" style="background-color: #039B7B;">Tipo Transaccion</th>
                                          <th class="white-text" style="background-color: #039B7B;">Abono</th>
                                          <th class="white-text" style="background-color: #039B7B;">Diferencia</th>
                                          <th class="white-text" style="background-color: #039B7B;">Venta Directa</th>
                                      </tr>
                                    </thead>
                            
                                    <tbody>
                                        @foreach ($tiposTransacciones as $tipo)
                                            @php
                                                
                                            @endphp
                                            <tr>
                                                <td>{{$tipo->nombre}}</td>
                                                <td>${{ number_format($tipo->total_abonos,0,'','.') }}</td>
                                                <td>${{ number_format($tipo->total_diferencias,0,'','.') }}</td>
                                                <td>${{ number_format($tipo->venta_directa,0,'','.') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tr>
                                        <td style=" text-align: center;"><strong>Subtotal:</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos"),0,'','.') }}</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_diferencias"),0,'','.') }}</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("venta_directa"),0,'','.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td style=" text-align: center;"><strong>Total:</strong></td>
                                        <td></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos")+$tiposTransacciones->sum("total_diferencias")+$tiposTransacciones->sum("venta_directa"),0,'','.') }}</strong></td>
                                    </tr>
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