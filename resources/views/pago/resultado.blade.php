<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $exito ? 'Pago confirmado' : 'Resultado del pago' }} — Botacura</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            background: white;
            border-radius: 16px;
            padding: 40px 32px;
            max-width: 440px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        }
        .icon {
            font-size: 56px;
            margin-bottom: 16px;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            color: {{ $exito ? '#1a7f4b' : '#c0392b' }};
        }
        .mensaje {
            font-size: 15px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        @if ($exito && isset($monto))
        .detalle {
            background: #f0faf4;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #333;
            text-align: left;
        }
        .detalle div { margin-bottom: 4px; }
        .detalle span { font-weight: 600; }
        @endif
        .logo {
            margin-top: 28px;
            font-size: 13px;
            color: #aaa;
        }
        .logo strong { color: #888; }
        .whatsapp-btn {
            display: inline-block;
            margin-top: 20px;
            background: #25D366;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">{{ $exito ? '✅' : '❌' }}</div>
        <h1>{{ $exito ? '¡Pago recibido!' : 'Pago no procesado' }}</h1>
        <p class="mensaje">{{ $mensaje }}</p>

        @if ($exito && isset($monto))
        <div class="detalle">
            <div>💰 Monto pagado: <span>${{ number_format($monto, 0, ',', '.') }}</span></div>
            @if (isset($orden))
            <div>📋 Orden: <span>{{ $orden }}</span></div>
            @endif
        </div>
        @endif

        <a class="whatsapp-btn" href="https://wa.me/56974484112" target="_blank">
            💬 Contactar a Botacura
        </a>

        <div class="logo">
            <strong>Botacura</strong> · Cajón del Maipo
        </div>
    </div>
</body>
</html>
