<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            width: 80mm;
            font-family: Arial, sans-serif;
            font-size: 12px;
            text-align: center;
            padding: 5px;
        }
        .ticket { width: 100%; }
        .title { font-size: 14px; font-weight: bold; }
        .line { border-top: 1px dashed black; margin: 5px 0; }
        .item { display: flex; justify-content: center; font-size: 12px; width: 100%; white-space: nowrap;}
        .etiqueta { font-size: 12px; font-weight: bold; margin-top: 10px; margin-right: 5%; text-align: left;}
        .subtotal { font-size: 12px; font-weight: bold; margin-top: 10px; margin-right: 7%; text-align: right;}
        .total { font-size: 14px; font-weight: bold; margin-top: 10px; margin-right: 7%; text-align: right;}
        .producto{
            text-align: left; 
            flex-grow: 1;  /* Permite que el nombre del producto ocupe todo el espacio disponible */
            margin-right: 5px; /* Agrega un pequeño margen para separar del precio */
            overflow: hidden; /* Evita desbordamiento */
            text-overflow: ellipsis;
        }
        .valor{        
            text-align: right; 
            flex-shrink: 0; /* Evita que el precio se reduzca de tamaño */
            min-width: 50px; /* Mantiene un ancho mínimo para asegurar alineación */
            margin-left: 5px; 
        }
    </style>
</head>
<body>
    <div class="ticket">

        <div class="title">Centro Recreativo Botacura LTDA.</div>
        <div>Cliente: {{ $nombre }}</div>
        <div>Fecha: {{ date('d/m/Y H:i') }}</div>
        <div class="line"></div>
        @php
            $propina = 0;
        @endphp

        <table style="width: 90%; border-collapse: collapse; margin-left:3%;">
            @if (!empty($listaConsumos))
                <div class="etiqueta">Productos</div>
                @foreach($listaConsumos as $detalle)
                    @php
                        $total += $detalle->subtotal;
                        $propina += $detalle->subtotal*0.1;
                    @endphp
                    <tr>
                        <td style="text-align: left; padding-right: 5px; word-wrap: break-word; max-width: 70%;">
                            {{ $detalle->producto->nombre}} X {{ $detalle->cantidad_producto }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            ${{ number_format($detalle->subtotal, 0, '', '.') }}
                        </td>
                    </tr>
                @endforeach
                .line
            @endif

            @if (!empty($listaServicios))
                <div class="etiqueta">Servicios</div>
                @foreach($listaServicios as $servicio)
                    @php
                        $total += $servicio->subtotal;
                    @endphp
                    <tr>
                        <td style="text-align: left; padding-right: 5px; word-wrap: break-word; max-width: 70%;">
                            {{ $servicio->servicio->nombre_servicio}} X {{ $servicio->cantidad_servicio }}
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            ${{ number_format($servicio->subtotal, 0, '', '.') }}
                        </td>
                    </tr>
                @endforeach
            @endif
        </table>

        {{-- @if (!empty($listaConsumos))
            <div class="etiqueta">Productos</div>
            @foreach($listaConsumos as $detalle)
                @php
                    $total += $detalle->subtotal;
                @endphp
                <div class="item">
                    <span class="producto">{{ $detalle->producto->nombre }}  X {{ $detalle->cantidad_servicio }}</span>
                    <span class="valor">${{ number_format($detalle->subtotal, 0,'','.') }}</span>
                </div>
            @endforeach
        @endif --}}

        {{-- @if (!empty($listaServicios))
        <div class="etiqueta">Servicios</div>
            @foreach($listaServicios as $servicio)
                @php
                    $total += $servicio->subtotal;
                @endphp
                <div class="item">
                    <span class="producto">{{ $servicio->servicio->nombre_servicio }} X {{ $servicio->cantidad_servicio }}</span>
                    <span class="valor">${{ number_format($servicio->subtotal, 0,'','.') }}</span>
                </div>
            @endforeach
        @endif --}}



        <div class="line"></div>
        <div class="subtotal">Sub-total: ${{ number_format($total, 0,'','.') }}</div>
        <div class="subtotal">Propina Sugerida: ${{ number_format($propina, 0,'','.') }}</div>
        <div class="total">Total: ${{ number_format($total+$propina, 0,'','.') }}</div>

        <div class="line"></div>
        <div>¡Gracias por su visita!</div>
    </div>
</body>
</html>
