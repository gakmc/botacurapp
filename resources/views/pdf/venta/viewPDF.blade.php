<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="{{ asset('assents/backoffice/css/materialize.css') }}" rel="stylesheet">

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
        <h3 class="right primario" style="margin-top: 7%">Detalle de venta</h3>
    </div>

    <div>
        <h6 class="right "><span class="primario">Fecha Visita:</span> {{$fecha_visita}}</h6>
        <h5 class="primario">Información del Cliente</h5>
        <h6><span class="primario">Nombre:</span> {{$nombre}}</h6>
        <h6><span class="primario">Contacto:</span> +{{$numero}}</h6>
    </div>

    <br>

    <div>
        <h5 class="primario">Información de Reserva</h5>
        <h6 class="left"><span class="primario">Programa:</span> {{$programa}}</h6>
        <h6 class="right "><span class="primario">Observación:</span> {{$observacion}}</h6>
        <h6 class="center"><span class="primario">Cantidad de asistentes:</span> {{$personas}} personas</h6>
    </div>

    <br>



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
                    <td>{{$menu->productoAcompanamiento->nombre}}</td>
                    <td>{{$menu->productoFondo->nombre}}</td>
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

    <br>

    <div>
        <h5 class="primario">Consumo Extra</h5>
        @if ($consumos->isEmpty())

        <h6 class="left"><span class="primario">Productos o Servicios:</span> No se registran consumos extras</h6>

        <br>
        @else



        <table class="striped">
            <thead>
                <tr>
                    <th class="primario">Producto o Servicio</th>
                    <th class="primario">Cantidad</th>
                    <th class="primario">Valor</th>
                    <th class="primario">Subtotal</th>
                    <th class="primario">Total</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($consumos as $index=>$consumo )
                @foreach ( $consumo->detallesConsumos as $detalles)
                <tr>
                    <td class="primario">{{$detalles->producto->nombre}}</td>
                    <td>X{{$detalles->cantidad_producto}}</td>
                    <td>${{number_format($detalles->producto->valor,0,'','.')}}</td>
                    <td>${{number_format($detalles->subtotal,0,'','.')}}</td>
                    @if ($detalles->genera_propina)
                    <td>${{number_format($detalles->subtotal * 1.1,0,'','.')}}</td>
                    @else
                    <td>${{number_format($detalles->subtotal,0,'','.')}}</td>
                    @endif
                </tr>
                @endforeach
                @foreach ($consumo->detalleServiciosExtra as $servicios)
                <tr>
                    <td class="primario">{{$servicios->servicio->nombre_servicio}}</td>
                    <td>X{{$servicios->cantidad_servicio}}</td>
                    <td>${{number_format($servicios->servicio->valor_servicio,0,'','.')}}</td>
                    <td>${{number_format($servicios->subtotal,0,'','.')}}</td>
                    <td>${{number_format($servicios->subtotal,0,'','.')}}</td>
                </tr>
                @endforeach
                <tr>
                    <td colspan="3"></td>
                    <td style="font-weight: bold">${{number_format($consumo->subtotal,0,'','.')}}</td>
                    <td style="font-weight: bold">${{number_format($total,0,'','.')}}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <br>

    <div>
        <h5 class="primario">Información de Pagos</h5>
        <h6 class="left"><span class="primario">Pago con propina:</span>{{$propina}}</h6>
        <h6 class="right "><span class="primario">Diferencia:</span> ${{number_format($venta->diferencia_programa,0,'','.')}}</h6>
        <h6 class="center"><span class="primario">Abono:</span> ${{number_format($venta->abono_programa,0,'','.')}}</h6>
    </div>
    <div>
        <h6 class="center"></h6>
        <h6 class="right"><span class="primario">Total:</span> ${{number_format($venta->abono_programa+$venta->diferencia_programa,0,'','.')}}</h6>
    </div>
    <br>


    <script src="{{ asset('assents/backoffice/js/materialize.min.js') }}"></script>

</body>

</html>