<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">

        <title>Detalle Venta {{$nombre}}</title>

    </head>

    <body>
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
            }

            #text {
                color: #039B7B;
            }

            .primario {
                color: #039B7B;
            }
        </style>

        <div>
            <img style="max-height: 150px;"
                src="https://botacura.cl/wp-content/uploads/2024/04/294235172_462864912512116_3346235978129441981_n-modified.png"
                alt="botacura logo" />
            <h3 class="right primario" style="margin-top: 7%">Detalle de visita</h3>
        </div>

        <div>
            <h6 class="right "><span class="primario">Fecha Visita:</span> {{$fecha_visita}}</h6>
            <h5 class="primario">Información del Cliente</h5>
            <h6 class="left"><span class="primario">Nombre:</span> {{$nombre}}</h6>
            <h6 class="right"><span class="primario">Contacto:</span> +{{$numero}}</h6>
            <h6 class="center">  </h6>
        </div>

        <br>

        <div>
            <h5 class="primario">Información de Reserva</h5>
            <h6 class="left"><span class="primario">Programa:</span> {{$programa}}</h6>
            {{-- <h6 class="right "><span class="primario">Observación:</span> {{$observacion}}</h6> --}}
            <h6 class="right"><span class="primario">Abono: </span> ${{number_format($venta->abono_programa,0,'','.')}}</h6>
            <h6 class="center"><span class="primario">Cantidad de asistentes:</span> {{$personas}} {{($personas >= 2) ? 'personas' : 'persona'}}</h6>
        </div>

        <br>


        {{-- @if ($menus->isEmpty())
            
        @else

            <div>
                <h5 class="primario">Menús</h5>

                <table class="striped">
                    <thead>
                        <tr>
                            <th class="primario">Menú</th>
                            <th class="primario">Entrada</th>
                            <th class="primario">Plato Fondo</th>
                            <th class="primario">Acompañamiento</th>
                            <th class="primario">Observaciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($menus as $index=>$menu )
                        <tr>
                            <td class="primario">Menú {{$index+1}}</td>
                            <td>{{$menu->productoEntrada->nombre}}</td>
                            <td>{{$menu->productoFondo->nombre}}</td>
                            <td>
                                @if ($menu->productoAcompanamiento == null)
                                Sin Acompañamiento
                                @else
                                {{ $menu->productoAcompanamiento->nombre }}
                                @endif

                            </td>
                            <td>
                                @if (is_null($menu->observacion))
                                No registra
                                @else
                                <span style="color: red">{{ $menu->observacion }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

            </div>
            
        @endif --}}


        <div class="col s12">
            <h5 class="primario">Consumo</h5>
            @if (is_null($consumo))
                <h6 class="left"><span class="primario">Productos o Servicios:</span> No se registran consumos extras</h6>
                <br>
            @else

                @php
                    $propina = 0;
                    $valor = 0;
                @endphp

                <table class="striped centered">
                    <thead>
                        <tr>
                            <th class="primario">Producto</th>
                            <th class="primario">Cantidad</th>
                            <th class="primario">Valor Unitario</th>
                            <th class="primario">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                            @foreach ( $consumo->detallesConsumos as $detalles)
                            <tr>
                                <td class="primario">{{$detalles->producto->nombre}}</td>
                                <td>X{{$detalles->cantidad_producto}}</td>
                                <td>${{number_format($detalles->producto->valor,0,'','.')}}</td>
                                <td>${{number_format($detalles->subtotal,0,'','.')}}</td>
                                @php
                                    $propina += $detalles->subtotal*0.1;
                                    $valor += $detalles->subtotal;
                                @endphp

                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <table>
                    <tr>
                        <td colspan="3"></td>
                        <td style="font-weight: bold; text-align:right; padding-top:0%;">Subtotal:
                            ${{number_format($valor,0,'','.')}}</td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                        <td style="font-weight: bold; text-align:right; padding-top:0%;">Propina sugerida (10%):
                            ${{number_format($propina,0,'','.')}}</td>
                    </tr>
                    <tr>
                        <td colspan="3"></td>
                        <td style="font-weight: bold; text-align:right; padding-top:0%;">Total consumo: ${{number_format($valor+$propina,0,'','.')}}</td>
                    </tr>

                </table>

            @endif
        </div>

        @if (!is_null($consumo) && $consumo->detalleServiciosExtra)
        @if ($consumo->detalleServiciosExtra->isNotEmpty())
        <div>
            <h5 class="primario">Servicio Extra</h5>

                <table class="striped centered">
                    <thead>
                        <tr>
                            <th class="primario">Servicio</th>
                            <th class="primario">Valor</th>
                            <th class="primario">Cantidad</th>
                            <th class="primario">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($consumo->detalleServiciosExtra as $servicios)
                        <tr>
                            <td class="primario">{{$servicios->servicio->nombre_servicio}}</td>
                            <td>${{number_format($servicios->servicio->valor_servicio,0,'','.')}}</td>
                            <td>X{{$servicios->cantidad_servicio}}</td>
                            <td>${{number_format($servicios->subtotal,0,'','.')}}</td>
                        </tr>
                        @endforeach

                    </tbody>
                </table>
            </div>
            @endif
            @endif

        <br>

        <div>
            <h5 class="primario">Diferencia Visita</h5>
            <h6 class="left"><span class="primario">Diferencia:</span></h6>
            <h6 class="right"><span>${{number_format($diferencia,0,'','.')}}</span></h6>
            {{-- Diferencia o total --}}
            <h6 class="center">  </h6>
        </div>
        <br>
        <br>
        <div>
            @if ($venta->diferencia_programa != null || $venta->diferencia_programa > 0)
                <h6 class="left"><span class="primario" style="font-weight: bold">Total Pagado: </span> 
                    ${{number_format($venta->diferencia_programa,0,'','.')}}
                </h6>
            @else
                <h6 class="left"><span class="primario" style="font-weight: bold">Total a Pagar: </span> 
                    ${{number_format($venta->total_pagar,0,'','.')}}
                </h6>
            @endif

        </div>
        <br>


        <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    </body>

</html>