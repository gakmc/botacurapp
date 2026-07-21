<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exito ? 'Pago confirmado' : 'Error en el pago' }} — Botacura</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: #f5f0eb; min-height: 100vh; display: flex; align-items: center;
               justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 20px; padding: 36px 28px;
                max-width: 400px; width: 100%; text-align: center;
                box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .icon { font-size: 56px; margin-bottom: 16px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 8px;
             color: {{ $exito ? '#2e7d32' : '#c62828' }}; }
        .mensaje { color: #666; font-size: 15px; margin-bottom: 24px; }
        .detalle { background: #f8f8f8; border-radius: 10px; padding: 16px; text-align: left;
                   margin-bottom: 24px; font-size: 14px; }
        .detalle .fila { display: flex; justify-content: space-between; padding: 5px 0;
                          border-bottom: 1px solid #eee; }
        .detalle .fila:last-child { border-bottom: none; }
        .detalle .key { color: #999; }
        .detalle .val { font-weight: 600; color: #333; }
        .btn { display: block; padding: 14px; background: #1976d2; color: #fff;
               border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 15px; }
        .footer { margin-top: 20px; font-size: 12px; color: #bbb; }
    </style>
</head>
<body>
<div class="card">
    @if($exito)
        <div class="icon">✅</div>
        <h1>{{ $mensaje }}</h1>
        <p class="mensaje">Tu pago fue procesado con éxito. Recibirás la confirmación en tu WhatsApp.</p>

        @if(isset($reserva) && $reserva)
        <div class="detalle">
            <div class="fila"><span class="key">Cliente</span><span class="val">{{ $reserva->nombre_cliente ?? '' }}</span></div>
            <div class="fila"><span class="key">Programa</span><span class="val">{{ $reserva->nombre_programa ?? '' }}</span></div>
            @if(isset($monto))
            <div class="fila"><span class="key">Monto pagado</span><span class="val">${{ number_format($monto, 0, ',', '.') }}</span></div>
            @endif
            @if(isset($auth_code) && $auth_code)
            <div class="fila"><span class="key">Código autorización</span><span class="val">{{ $auth_code }}</span></div>
            @endif
            @if(isset($card) && $card)
            <div class="fila"><span class="key">Tarjeta</span><span class="val">**** {{ $card }}</span></div>
            @endif
        </div>
        @endif

    @else
        <div class="icon">❌</div>
        <h1>{{ $mensaje }}</h1>
        <p class="mensaje">No se pudo procesar tu pago. Puedes intentarlo de nuevo o contactarnos.</p>

        @if(isset($reserva) && $reserva)
        <a href="{{ route('pago.opciones', ['reserva_id' => $reserva->id]) }}" class="btn">
            Volver a opciones de pago
        </a>
        @endif
    @endif

    <div class="footer">¿Dudas? +56 9 7448 4112 · hola@botacura.cl</div>
</div>
</body>
</html>
