<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago de Reserva — Botacura</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f0eb;
            color: #2d2d2d;
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 480px; margin: 0 auto; }

        .logo-area {
            text-align: center;
            padding: 24px 0 16px;
        }
        .logo-area h1 {
            font-size: 26px;
            font-weight: 700;
            color: #3a3a3a;
            letter-spacing: -0.5px;
        }
        .logo-area p { color: #888; font-size: 13px; margin-top: 4px; }

        .card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }

        .reserva-info { border-bottom: 1px solid #f0f0f0; padding-bottom: 16px; margin-bottom: 16px; }
        .reserva-info h2 { font-size: 16px; color: #555; font-weight: 500; margin-bottom: 8px; }
        .reserva-info .cliente { font-size: 20px; font-weight: 700; color: #222; }
        .reserva-info .detalle { font-size: 14px; color: #777; margin-top: 4px; }
        .reserva-info .total {
            margin-top: 12px;
            font-size: 22px;
            font-weight: 700;
            color: #2e7d32;
        }

        .metodo-titulo {
            font-size: 15px;
            font-weight: 600;
            color: #444;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .metodo-titulo span { font-size: 20px; }

        /* Transferencia */
        .ref-code {
            background: #fff8e1;
            border: 2px dashed #f9a825;
            border-radius: 10px;
            padding: 14px 16px;
            margin: 12px 0;
            text-align: center;
        }
        .ref-code .label { font-size: 12px; color: #888; margin-bottom: 4px; }
        .ref-code .code {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #e65100;
            font-family: 'Courier New', monospace;
        }
        .ref-code .instruccion {
            font-size: 12px;
            color: #777;
            margin-top: 6px;
        }

        .banco-datos { margin-top: 12px; }
        .banco-fila {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        .banco-fila:last-child { border-bottom: none; }
        .banco-fila .key { color: #999; }
        .banco-fila .val { font-weight: 600; color: #333; }

        .montos-nota {
            background: #e8f5e9;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 13px;
            color: #388e3c;
            margin-top: 12px;
        }

        /* Webpay */
        .divider {
            text-align: center;
            margin: 4px 0;
            color: #bbb;
            font-size: 12px;
            position: relative;
        }
        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e0e0e0;
        }
        .divider::before { left: 0; }
        .divider::after { right: 0; }

        .btn-webpay {
            display: block;
            width: 100%;
            padding: 16px;
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 12px;
            text-align: center;
            text-decoration: none;
            transition: background .2s;
        }
        .btn-webpay:hover { background: #1565c0; }
        .btn-webpay small { display: block; font-size: 12px; font-weight: 400; opacity: .85; margin-top: 2px; }

        .webpay-logo {
            text-align: center;
            margin-top: 10px;
        }
        .webpay-logo img { height: 32px; opacity: .7; }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #bbb;
            padding: 16px 0 32px;
        }

        @media (max-width: 400px) {
            .ref-code .code { font-size: 18px; }
        }
    </style>
</head>
<body>
<div class="container">

    <div class="logo-area">
        <h1>Botacura</h1>
        <p>Termas &amp; Wellness</p>
    </div>

    <!-- Detalle reserva -->
    <div class="card">
        <div class="reserva-info">
            <h2>Tu reserva</h2>
            <div class="cliente">{{ $reserva->nombre_cliente }}</div>
            <div class="detalle">
                {{ $reserva->nombre_programa }} —
                {{ \Carbon\Carbon::parse($reserva->fecha_visita)->format('d \d\e F Y') }} —
                {{ $reserva->cantidad_personas }} persona{{ $reserva->cantidad_personas > 1 ? 's' : '' }}
            </div>
            <div class="total">${{ number_format($reserva->total_pagar, 0, ',', '.') }}</div>
        </div>

        {{-- Alerta de errores --}}
        @if($errors->any())
            <div style="background:#ffebee;border-radius:8px;padding:12px;margin-bottom:12px;color:#c62828;font-size:13px;">
                {{ $errors->first() }}
            </div>
        @endif
        @if(session('warning'))
            <div style="background:#fff8e1;border-radius:8px;padding:12px;margin-bottom:12px;color:#e65100;font-size:13px;">
                {{ session('warning') }}
            </div>
        @endif

        <!-- OPCIÓN 1: Transferencia -->
        <div class="metodo-titulo"><span>🏦</span> Transferencia bancaria</div>

        <div class="ref-code">
            <div class="label">Código de referencia (cópialo en el mensaje/motivo)</div>
            <div class="code">{{ $reserva->referencia_transferencia }}</div>
            <div class="instruccion">⚠️ Es OBLIGATORIO incluirlo para confirmar tu reserva automáticamente</div>
        </div>

        <div class="banco-datos">
            <div class="banco-fila">
                <span class="key">Banco</span>
                <span class="val">{{ $cuentaBank['banco'] }}</span>
            </div>
            <div class="banco-fila">
                <span class="key">Tipo de cuenta</span>
                <span class="val">{{ $cuentaBank['tipo'] }}</span>
            </div>
            <div class="banco-fila">
                <span class="key">Número de cuenta</span>
                <span class="val">{{ $cuentaBank['numero'] }}</span>
            </div>
            <div class="banco-fila">
                <span class="key">RUT</span>
                <span class="val">{{ $cuentaBank['rut'] }}</span>
            </div>
            <div class="banco-fila">
                <span class="key">Nombre</span>
                <span class="val">{{ $cuentaBank['nombre'] }}</span>
            </div>
            <div class="banco-fila">
                <span class="key">Email</span>
                <span class="val">{{ $cuentaBank['email'] }}</span>
            </div>
        </div>

        <div class="montos-nota">
            💚 Abono 50%: <strong>${{ number_format($abono50, 0, ',', '.') }}</strong><br>
            Saldo de <strong>${{ number_format($abono50, 0, ',', '.') }}</strong> se paga el día de tu visita.
        </div>

        <!-- Divider -->
        <div class="divider" style="margin: 20px 0;">o paga el total online</div>

        <!-- OPCIÓN 2: Webpay -->
        <div class="metodo-titulo"><span>💳</span> Webpay Plus</div>

        <form action="{{ route('pago.webpay.init', ['reserva_id' => $reserva->id]) }}" method="POST">
            @csrf
            <button type="submit" class="btn-webpay">
                Pagar ${{ number_format($reserva->total_pagar, 0, ',', '.') }} con tarjeta
                <small>Débito, Crédito o Prepago · Seguro con Transbank</small>
            </button>
        </form>

        <div class="webpay-logo">
            <small style="color:#bbb;font-size:11px;">Procesado con seguridad por Transbank</small>
        </div>

    </div><!-- /card -->

    <div class="footer">
        ¿Dudas? Escríbenos al +56 9 7448 4112 o hola@botacura.cl
    </div>
</div>
</body>
</html>
