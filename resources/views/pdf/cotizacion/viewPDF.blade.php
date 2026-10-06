<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotización N.º {{ $cotizacion->id }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; margin: 30px; margin-top: 0px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { max-height: 180px; }
        .info-table, .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .info-table td { padding: 4px 0; }
        .items-table th, .items-table td { border: 1px solid #ccc; padding: 8px; }
        .items-table th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .highlight { color: #039B7B; font-weight: bold; }
        .enlaces { color: #777; text-decoration: none; }
        .terminos { font-size: 10.5px; line-height: 1.45; }
        .terminos li { margin-bottom: 3px; }
        @page { margin: 50px 30px; footer: footer; }
    </style>
</head>
<body>
@php
    $ivaIncluido = (bool) $cotizacion->iva_incluido;
    $suma = (int) $cotizacion->items->sum('total');
    if ($ivaIncluido) {
        $total = $suma;
        $neto = (int) round($suma / 1.19);
        $iva = $total - $neto;
    } else {
        $neto = $suma;
        $iva = (int) round($suma * 0.19);
        $total = $neto + $iva;
    }
@endphp

    <div class="header">
        <img src="{{ public_path('images/logo/botacura-logo-green.png') }}" alt="Logo Botacura" class="logo">
        <p>Camino al Volcán 13274, El Manzano km 27, San José de Maipo, Región Metropolitana</p>
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
            <td class="text-right"><strong>Fecha reserva:</strong> {{ $reserva }}</td>
        </tr>
        <tr>
            <td class="text-left"><strong>Correo:</strong> {{ $cotizacion->correo }}</td>
            <td class="text-right"><strong>Validez:</strong> {{ $cotizacion->validez_dias }} días</td>
        </tr>
        @if($cotizacion->observaciones)
        <tr>
            <td class="text-left" colspan="2"><strong>Observaciones:</strong> {{ $cotizacion->observaciones }}</td>
        </tr>
        @endif
    </table>

    <h3 class="highlight">Detalle de cotización</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Cantidad</th>
                <th>{{ $ivaIncluido ? 'Valor unitario' : 'Valor neto' }}</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cotizacion->items as $item)
                <tr>
                    <td>
                        <span class="highlight">
                            {{ $item->itemable->nombre_programa ?? $item->itemable->nombre_servicio ?? $item->itemable->nombre }}
                        </span>
                        @if ($item->itemable_type === 'App\Servicio' && $item->itemable->duracion > 0)
                            <br><span style="color:#777;font-size:10px;">({{ $item->itemable->duracion }} min)</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->cantidad }}</td>
                    <td class="text-right">${{ number_format($item->valor_neto, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($item->total, 0, ',', '.') }}</td>
                </tr>
                @if ($item->itemable_type === 'App\Programa' && $item->itemable->servicios)
                    @foreach ($item->itemable->servicios as $servicio)
                        <tr>
                            <td>{{ $servicio->nombre_servicio }}{{ $servicio->duracion > 0 ? ' ('.$servicio->duracion.' min)' : '' }}</td>
                            <td></td><td></td><td></td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right"><strong>NETO</strong></td>
                <td class="text-right">${{ number_format($neto, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>IVA (19%)</strong></td>
                <td class="text-right">${{ number_format($iva, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>${{ number_format($total, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>
    @if($ivaIncluido)
        <p style="font-size:10px;color:#777;text-align:right;">Valores unitarios con IVA incluido.</p>
    @endif

    <br>
    <div class="terminos">
        <strong style="color: #C97D2F;">TÉRMINOS Y CONDICIONES</strong>
        <ol>
            <li>Cotización sujeta a disponibilidad hasta concretar la reserva.</li>
            <li>Para reservar: abono del 50% por transferencia o pago del 100% con Webpay / link de pago. Los planes Extendidos y las promociones se pagan solo por transferencia. Se acepta un único pago por reserva.</li>
            <li>La reserva queda confirmada cuando Botacura verifica el pago y envía la confirmación. El saldo se paga el día de la visita, antes del ingreso.</li>
            <li>Si necesitas factura, infórmalo al reservar junto con los datos de facturación. Una vez emitida, no se modifica.</li>
            <li>Se permite una única reprogramación, solicitada con al menos 72 horas hábiles de anticipación, sujeta a disponibilidad y para usar dentro de 45 días corridos. No se realizan devoluciones de dinero.</li>
            <li>No se permite ingresar alimentos ni bebidas externas, alcohol, mascotas, parlantes, pelotas ni flotadores.</li>
        </ol>
        Políticas completas: www.botacura.cl/privacy-policy/
    </div>

<htmlpagefooter name="footer">
    <div style="width: 100%; font-size: 11px; text-align: center; border-top: 1px solid #ccc; padding-top: 5px; color: #777;">
        <a class="enlaces" href="https://www.instagram.com/botacura_cajondelmaipo/">@botacura_cajondelmaipo</a> • <a class="enlaces" href="https://wa.me/56974484112">+56 9 7448 4112</a> • hola@botacura.cl • <a class="enlaces" href="https://www.botacura.cl">www.botacura.cl</a>
    </div>
</htmlpagefooter>
</body>
</html>
