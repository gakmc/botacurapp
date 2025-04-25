@extends('themes.backoffice.layouts.admin')

@section('title','Finanzas')

@section('head')
@endsection

@section('breadcrumbs')
<li>Caja del {{ ucfirst($nombreMes) }}</li>
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


                        <h5><strong>Detalle de caja: {{ ucfirst($nombreMes) }}</strong></h5>
                        <table class="responsive-table">
                            <thead>
                                <tr>
                                    <th>Reserva</th>
                                    <th>Programa</th>
                                    <th>Diferencia</th>
                                    <th>Pendiente</th>
                                    <th>Propina</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
 
                                <tbody>
                                    @foreach ($ventas as $venta)
                                        <tr>
                                            <td><a href="{{route("backoffice.reserva.show",$venta->reserva->id)}}">{{$venta->reserva->cliente->nombre_cliente}}</a></td>
                                            <td>{{$venta->reserva->programa->nombre_programa}}</td>

                                            @if ($venta->diferencia_programa == null)
                                                <td>Por pagar</td>
                                            @else
                                                <td class="green-text">${{ number_format($venta->reserva->programa->valor_programa*$venta->reserva->cantidad_personas - $venta->abono_programa, 0, ',', '.') }}</td>
                                            @endif
                                            <td @if ($venta->total_pagar > 0) class="red-text" @endif>
                                                ${{ number_format($venta->total_pagar, 0, ',', '.') }}
                                            </td>
                                            <td>${{($venta->consumo->propina != null) ? number_format($venta->consumo->propina->cantidad,0,'','.') : 0}}</td>
                                            <td>${{ number_format($venta->reserva->programa->valor_programa*$venta->reserva->cantidad_personas, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                
                                
                
                        </table>
                        
                        <div class="center">
                            {{ $ventas->links('vendor.pagination.materialize') }}
                        </div>
                    </div>
                </div>



                <div class="card-panel">
                    <div class="row">

                        <div class="col s12 m3">
                            <h5><strong>Resumen de programas</strong></h5>
                            <table class="striped centered">  
                                <thead>
                                  <tr>
                                      <th class="white-text" style="background-color: #039B7B">Programa</th>
                                      
                                  </tr>
                                </thead>
                        
                                <tbody>
                                    @foreach ($programas as $programa)
                                        @php
                                            $contratado = ($programa->total_programas == 0 || $programa->total_programas > 1) ? 'contratados' : 'contratado';
                                        @endphp
                                        <tr>
                                            <td>{{$programa->nombre_programa}}: 
                                            {{ $programa->total_programas }} {{$contratado}}</td>
                                            
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>






                        <div class="col s12 m3">
                            <h5><strong>Resumen Medios de Pago</strong></h5>
                            <table class="striped bordered" >
                                <thead>
                                  <tr>
                                      <th class="white-text" style="background-color: #039B7B;">Medio de Pago</th>
                                      <th class="white-text" style="background-color: #039B7B">Total Dia</th>
                                  </tr>
                                </thead>
                        
                                <tbody>
                                    @foreach ($tiposTransacciones as $transaccion)
                                        @php
                                            
                                        @endphp
                                        <tr>
                                            <td>{{$transaccion->nombre}}:</td>
                                            <td>${{ number_format($transaccion->total_abonos+$transaccion->total_diferencias,0,'','.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tr>
                                    <td style=" text-align: center;"><strong>Total:</strong></td>
                                    <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos")+$tiposTransacciones->sum("total_diferencias"),0,'','.') }}</strong></td>
                                </tr>
                            </table>
                        </div>






                        <div class="col s12 m3">
                            <h5><strong>Resumen Propinas</strong></h5>

                            <table class="striped bordered" >
                                <thead>
                                  <tr>
                                      <th class="white-text" style="background-color: #039B7B;">Propinas</th>
                                      <th class="white-text" style="background-color: #039B7B;"></th>
                                  </tr>
                                </thead>
                        
                                <tbody>

                                    <tr>
                                        <td>
                                            Total:
                                        </td>
                                        <td>
                                            ${{number_format($totalPropina,0,'','.')}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Cantidad de Colaboradores:
                                        </td>
                                        <td>
                                            {{$cantidadUsuarios}}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            Propinas por colaborador:
                                        </td>
                                        <td>
                                            ${{number_format($propinaPorUsuario,0,'','.')}}
                                        </td>
                                    </tr>


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