@extends('themes.backoffice.layouts.admin')

@section('title','Finanzas')

@section('breadcrumbs')
<li><a href="{{route('backoffice.admin.ingresos')}}">Ingresos detallados</a></li>
<li><a href="{{ route('backoffice.admin.ingresos.detalleMes', [$anio, $mes]) }}">Detalles</a></li>
<li>{{ ucfirst($nombreMes) }}</li>
@endsection

@section('content')

<div class="section">
    <p class="caption"><strong>Ingresos</strong></p>
    <div class="row">

    @if ($cantidadGc > 0)
    <div class="col s12 m3">
        <div class="card-panel gradient-45deg-light-blue-cyan">
            <span class="white-text">GiftCard: <strong>{{number_format($cantidadGc,0,'','.')}}</strong> Total: <strong>${{number_format($totalGc,0,'','.')}}</strong></span>
        </div>
    </div>
    @endif

    </div>
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
                                    <th>Consumo</th>
                                    <th>Servicios Extra</th>
                                    <th>Total pagar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalConsumo = 0;
                                    $totalServicios = 0;
                                    $diferencias = 0;
                                @endphp

                                @foreach ($ventas as $venta)
                                    @php
                                        $consumoSinPropina = 0;
                                        $serviciosSinPropina = 0;
                                        $diferencia = 0;
                                        $isGiftCard = $venta->pagado_con_giftcard ?? false;

                                        if (!$isGiftCard) {
                                            $totalDiferencia = $venta->pendiente_de_pago ? $venta->total_pagar : $venta->diferencia_programa;
                                            $diferencias += $totalDiferencia;
                                            $diferencia = $totalDiferencia;
                                        }

                                        if ($venta->consumo != null) {
                                            $consumoSinPropina = $venta->consumo->detallesConsumos->sum("subtotal");  
                                            $serviciosSinPropina = $venta->consumo->detalleServiciosExtra->sum("subtotal");
                                        }

                                        $totalConsumo += $consumoSinPropina;
                                        $totalServicios += $serviciosSinPropina;
                                    @endphp
                                    <tr>
                                        <td>{{ $venta->reserva->cliente->nombre_cliente }}</td>
                                        <td>
                                            @if($isGiftCard)
                                                <span class="blue-text">GiftCard</span>
                                            @else
                                                {{ $venta->reserva->programa->nombre_programa }}
                                            @endif
                                        </td>

                                        <td class="green-text">
                                            ${{ $isGiftCard ? '0' : number_format($venta->abono_programa, 0, ',', '.') }}
                                        </td>

                                        @if ($venta->pendiente_de_pago && !$isGiftCard)
                                            <td>Por pagar</td>
                                        @elseif ($isGiftCard)
                                            <td>0</td>
                                        @else
                                            <td class="green-text">${{ number_format($venta->diferencia_programa, 0, ',', '.') }}</td>
                                        @endif

                                        <td @if ($venta->total_pagar > 0 && !$isGiftCard) class="red-text" @endif>
                                            @if($isGiftCard)
                                                0
                                            @else
                                                ${{ number_format($venta->total_pagar, 0, ',', '.') }}
                                            @endif
                                        </td>

                                        <td>${{ number_format($consumoSinPropina, 0, ',', '.') }}</td>
                                        <td>${{ number_format($serviciosSinPropina, 0, ',', '.') }}</td>

                                        <td>
                                            ${{ number_format(
                                                ($isGiftCard ? 0 : $venta->abono_programa) +
                                                ($isGiftCard ? 0 : $diferencia) +
                                                $consumoSinPropina +
                                                $serviciosSinPropina, 0, ',', '.')
                                            }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="center">
                            {{ $ventas->links('vendor.pagination.materialize') }}
                        </div>
                    </div>
                </div>

                {{-- Resumenes --}}
                <div class="card-panel">
                    <div class="row">

                        {{-- Resumen de programas --}}
                        <div class="col s12 m3">
                            <h5><strong>Resumen de programas</strong></h5>
                            <table class="striped centered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Programa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($programas as $programa)
                                        @php
                                            $contratado = ($programa->total_programas == 0 || $programa->total_programas > 1) ? 'contratados' : 'contratado';
                                        @endphp
                                        <tr>
                                            <td>{{ $programa->nombre_programa }}: {{ $programa->total_programas }} {{ $contratado }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Resumen de transacciones --}}
                        <div class="col s12 m3">
                            <h5><strong>Resumen de transacciones</strong></h5>
                            <table class="striped bordered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Tipo Transaccion</th>
                                        <th class="white-text" style="background-color: #039B7B;">Pagos recibidos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tiposTransacciones as $tipo)
                                        <tr>
                                            <td>{{ $tipo->nombre }}</td>
                                            <td>${{ number_format($tipo->total_abonos + $tipo->total_diferencias + $tipo->venta_directa, 0, '', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td style=" text-align: center;"><strong>Total:</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos")+$tiposTransacciones->sum("total_diferencias")+$tiposTransacciones->sum("venta_directa"),0,'','.') }}</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- Resumen del día --}}
                        <div class="col s12 m3">
                            <h5><strong>Resumen del día</strong></h5>
                            <table class="striped bordered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Total Día</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Total Entradas</td>
                                        <td>${{ number_format($tiposTransacciones->sum("total_abonos") + $diferencias, 0, '', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Abonos</td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos"),0,'','.') }}</strong></td>
                                    </tr>
                                    <tr>
                                        <td>Total Diferencias</td>
                                        <td>${{ number_format($diferencias,0,'','.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Total Consumo</td>
                                        <td>${{ number_format($totalConsumo + $tiposTransacciones->sum("venta_directa"),0,'','.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Servicios Extras</td>
                                        <td>${{ number_format($totalServicios,0,'','.') }}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Total Día</strong></td>
                                        <td><strong>${{ number_format($tiposTransacciones->sum("total_abonos") + $diferencias + $totalConsumo + $totalServicios,0,'','.') }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- Poro Poro --}}
                        <div class="col s12 m3">
                            <h5><strong>Ventas Poro Poro del día</strong></h5>
                            <table class="striped bordered">
                                <thead>
                                    <tr>
                                        <th class="white-text" style="background-color: #039B7B;">Tipo</th>
                                        <th class="white-text" style="background-color: #039B7B;">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tiposTransacciones as $tipo)
                                        <tr>
                                            <td>{{ $tipo->nombre }}</td>
                                            <td>${{ number_format($tipo->poro,0,'','.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td style=" text-align: center;"><strong>Total:</strong></td>
                                        <td>${{ number_format($tiposTransacciones->sum('poro'),0,'','.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
