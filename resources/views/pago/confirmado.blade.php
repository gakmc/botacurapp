<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva confirmada — Botacura</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: #f5f0eb; min-height: 100vh; display: flex; align-items: center;
               justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 20px; padding: 36px 28px;
                max-width: 400px; width: 100%; text-align: center;
                box-shadow: 0 4px 24px rgba(0,0,0,.08); }
        .icon { font-size: 56px; margin-bottom: 16px; }
        h1 { font-size: 22px; font-weight: 700; color: #2e7d32; margin-bottom: 8px; }
        p { color: #666; font-size: 15px; }
        .footer { margin-top: 24px; font-size: 12px; color: #bbb; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">🎉</div>
    <h1>¡Tu pago ya fue registrado!</h1>
    <p>La reserva de <strong>{{ $reserva->nombre_cliente }}</strong> está confirmada.<br>¡Te esperamos en Botacura!</p>
    <div class="footer">¿Dudas? +56 9 7448 4112 · hola@botacura.cl</div>
</div>
</body>
</html>
