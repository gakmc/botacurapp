@extends('themes.backoffice.layouts.admin')

@section('title','Finanzas')

@section('head')
@endsection

@section('breadcrumbs')
<li><a href="{{route('backoffice.admin.ingresos')}}">Ingresos detallados</a></li>
<li><a href="{{ route('backoffice.admin.ingresos.detalleMes', [$anio, $mes]) }}">Detalles</a></li>
<li>{{ ucfirst($nombreMes) }}</li>
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
                                    <th>Reserva</th>
                                    <th>Programa</th>
                                    <th>Abono</th>
                                    <th>Diferencia</th>
                                    <th>Pendiente</th>
                                    <th>Total pagar</th>
                                </tr>
                            </thead>
 
                                <tbody>
                                    @foreach ($ventas as $venta)
                                        <tr>
                                            <td>{{$venta->reserva->cliente->nombre_cliente}}</td>
                                            <td>{{$venta->reserva->programa->nombre_programa}}</td>
                                            <td class="green-text">${{ number_format($venta->abono_programa, 0, ',', '.') }}</td>
                                            @if ($venta->diferencia_programa == null)
                                                <td>Por pagar</td>
                                            @else
                                                <td class="green-text">${{ number_format($venta->reserva->programa->valor_programa*$venta->reserva->cantidad_personas - $venta->abono_programa, 0, ',', '.') }}</td>
                                                {{-- CONSIDERAR RESTAR TOTAL PAGAR DE ABONO --}}
                                            @endif
                                            <td @if ($venta->total_pagar > 0) class="red-text" @endif>
                                                ${{ number_format($venta->total_pagar, 0, ',', '.') }}
                                            </td>
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




            </div>
        </div>
    </div>
</div>


  
@endsection


@section('foot')




@endsection