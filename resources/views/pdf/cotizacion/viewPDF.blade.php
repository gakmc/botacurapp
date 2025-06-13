<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización N.º {{ $cotizacion->id }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            margin: 30px;
            margin-top: 0px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            max-height: 120px;
        }

        .info-table, .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .info-table td {
            padding: 4px 0;
        }

        .items-table th, .items-table td {
            border: 1px solid #ccc;
            padding: 8px;
        }

        .items-table th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .highlight {
            color: #039B7B;
            font-weight: bold;
        }

        .enlaces {
            color: #777;
            text-decoration: none;
        }

        @page {
            margin: 50px 30px;
            footer: footer;
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="https://botacura.cl/wp-content/uploads/2024/04/logo.png" alt="Logo Botacura" class="logo">
        <p>Cam. Al Volcán 13274, El Manzano, San José de Maipo, Región Metropolitana</p>
        <p>Centro de relajación y descanso</p>
        <h2 class="highlight">Cotización N.º {{ $cotizacion->id }}</h2>
    </div>

    <table class="info-table">
        <tr>
            <td class="text-left"><strong>Cliente:</strong> {{ $cotizacion->cliente }}</td>
            <td class="text-right"><strong>Emitida:</strong> {{ $emitida }}</td>
        </tr>
        <tr>
            <td class="text-left"><strong>Solicitante:</strong> {{ $cotizacion->solicitante }}</td>
            <td class="text-right"><strong>Fecha Reserva:</strong> {{ $reserva }}</td>
        </tr>
        <tr>
            <td class="text-left"><strong>Correo:</strong> {{ $cotizacion->correo }}</td>
            <td class="text-right"><strong>Validez:</strong> {{ $cotizacion->validez_dias }} días</td>
        </tr>
    </table>

    <h3 class="highlight">Detalle de Cotización</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>Valor Neto</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @php $subtotal = 0; $iva = 0; @endphp
            @foreach($cotizacion->items as $item)
                <tr>
                    <td>
                        <span class="highlight">
                            {{ $item->itemable->nombre_programa ?? $item->itemable->nombre_servicio ?? $item->itemable->nombre }}
                        </span>
                    </td>
                    <td class="text-right">{{ $item->cantidad }}</td>
                    <td class="text-right">${{ number_format($item->valor_neto, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>

                {{-- Servicios del programa --}}
                @if ($item->itemable_type === 'App\Programa' && $item->itemable->servicios)
                    @foreach ($item->itemable->servicios as $servicio)
                        <tr>
                            <td>{{ $servicio->nombre_servicio }}</td>
                            <td></td><td></td><td></td>
                        </tr>
                    @endforeach
                @endif

                @php $subtotal += $item->total; @endphp
            @endforeach
        </tbody>
        <tfoot>
            @php $iva = $subtotal * 0.19; @endphp
            <tr>
                <td colspan="3" class="text-right"><strong>SUBTOTAL</strong></td>
                <td class="text-right">${{ number_format($subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>IVA (19%)</strong></td>
                <td class="text-right">${{ number_format($iva, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right">${{ number_format($subtotal + $iva, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>


<!-- Términos y condiciones -->
<br><br>
<div style="text-align: center; font-size: 12px;">
    <strong style="color: #C97D2F;">TÉRMINOS Y CONDICIONES</strong><br>
    1.- No se permite el ingreso de ningún tipo de bebestible al recinto<br>
    2.- Pago con tarjeta de débito/crédito debe agregar 3% comisión<br>
    3.- No se permiten modificaciones a la reserva
</div>

<br><br>

<htmlpagefooter name="footer">
    <div style="width: 100%; font-size: 11px; text-align: center; border-top: 1px solid #ccc; padding-top: 5px; color: #777;">
        <a class="enlaces" href="https://www.instagram.com/botacura_cajondelmaipo/" target="_blank">@botacura_cajondelmaipo</a> • <a class="enlaces" href="https://api.whatsapp.com/send/?phone=56982720582&text=Hola%2C+quer%C3%ADa+consultar+por+&type=phone_number&app_absent=0" target="_blank">+569 8272 0582</a> • hola@botacura.cl • <a class="enlaces" href="www.botacura.cl" target="_blank">www.botacura.cl</a>
    </div>
</htmlpagefooter>
</body>
</html>