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
                                    <th>Diferencia</th>
                                    <th>Pendiente</th>
                                    <th>Consumo</th>
                                    <th>Servicios Extra</th>
                                    <th>Propina</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
 
                                <tbody>


                                    @foreach ($ventas as $venta)
                                    @php
                                        $consumoSinPropina = 0;
                                        $serviciosSinPropina = 0;
                                        $posiblePropina = 0;

                                        $totalDiferencia = ($venta->pendiente_de_pago) ? $venta->total_pagar : $venta->diferencia_programa;

                                        if ($venta->consumo != null)
                                        {
                                            $consumoSinPropina = $venta->consumo->detallesConsumos->sum("subtotal");  
                                            $serviciosSinPropina = $venta->consumo->detalleServiciosExtra->sum("subtotal");

                                                // if ($venta->consumo->detallesConsumos->contains('genera_propina', true)) {
                                                //     $posiblePropina = $consumoSinPropina*0.1;
                                                //     $totalPosiblePropina += $posiblePropina;
                                                // }
                                             
                                        }

                                    @endphp
                                        <tr>
                                            <td><a href="{{route("backoffice.reserva.show",$venta->reserva->id)}}">{{$venta->reserva->cliente->nombre_cliente}}</a></td>

                                            @if ($venta->pendiente_de_pago)
                                                <td >
                                                    <a class="btn-small disabled"><span class="red-text">Por Pagar</span><i class='material-icons red-text right '>cancel</i></a>
                                                </td>
                                            @else
                                                <td class="green-text">
                                                    <a class="btn-small disabled"><span class="green-text">Pagado</span><i class='material-icons green-text right '>check_circle</i></a>
                                                </td>
                                            @endif
                                            <td @if ($venta->total_pagar > 0) class="red-text" @endif>
                                                ${{ number_format($venta->total_pagar, 0, ',', '.') }}
                                            </td>

                                            <td>
                                                @if ($consumoSinPropina >= 0)
                                                    ${{ number_format($consumoSinPropina, 0, ',', '.') }}
                                                @endif
                                            </td>
                                            <td>
                                                @if ($serviciosSinPropina >= 0)
                                                ${{ number_format($serviciosSinPropina, 0, ',', '.') }}
                                            @endif
                                            </td>

                                            <td>
                                                ${{ number_format(optional(optional($venta->consumo)->propina)->cantidad ?? 0, 0, ',', '.') }}
                                            </td>

                                            @php
                                                $propinaReal = optional(optional($venta->consumo)->propina)->cantidad ?? 0;
                                            @endphp
                                            <td>${{ number_format($consumoSinPropina+$totalDiferencia+$serviciosSinPropina, 0, ',', '.') }}</td>
                                            <td>${{ number_format($consumoSinPropina+$totalDiferencia+$serviciosSinPropina+$propinaReal, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                
                                
                
                        </table>
                        
                        <div class="center">
                            {{ $ventas->links('vendor.pagination.materialize') }}
                        </div>
                    </div>
                </div>


                @if (Auth::user()->has_role(config('app.jefe_local_role')))
                    
                

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
                                        {{-- <th class="white-text" style="background-color: #039B7B">Venta Directa</th>
                                        <th class="white-text" style="background-color: #039B7B">Poro Poro</th> --}}
                                    </tr>
                                    </thead>
                            
                                    <tbody>
                                        @foreach ($tiposTransacciones as $transaccion)
                                            @php
                                                
                                            @endphp
                                            <tr>
                                                <td>{{$transaccion->nombre}}:</td>
                                                <td>${{ number_format($transaccion->total_diferencias+$transaccion->venta_directa+$transaccion->poro_poro,0,'','.') }}</td>
                                                {{-- <td>${{ number_format($transaccion->venta_directa,0,'','.') }}</td>
                                                <td>${{ number_format($transaccion->poro_poro,0,'','.') }}</td> --}}
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    {{-- <tr>
                                        <td style=" text-align: center;"><strong>Sub-Total:</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_diferencias"),0,'','.') }}</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("venta_directa"),0,'','.') }}</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("poro_poro"),0,'','.') }}</strong></td>
                                    </tr> --}}
                                    <tr>
                                        <td style=" text-align: center;"><strong>Total:</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_diferencias")+$tiposTransacciones->sum("venta_directa")+$tiposTransacciones->sum("poro_poro"),0,'','.') }}</strong></td>
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

                                        {{-- <tr>
                                            <td>
                                                Propinas Sugeridas:
                                            </td>
                                            <td>
                                                ${{number_format($totalPosiblePropina+($ventaDirectaTotalPropina ?? 0),0,'','.')}}
                                            </td>
                                        </tr> --}}
                                        <tr>
                                            <td>
                                                Propinas Pagadas:
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
                @endif

            </div>
        </div>
    </div>
</div>


  
@endsection


@section('foot')




@endsection
