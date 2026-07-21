<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirigiendo a Webpay...</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center;
               min-height: 100vh; background: #f5f0eb; flex-direction: column; gap: 16px; color: #444; }
        .spinner { width: 40px; height: 40px; border: 4px solid #e0e0e0;
                   border-top-color: #1976d2; border-radius: 50%; animation: spin 0.8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { font-size: 15px; }
    </style>
</head>
<body>
    <div class="spinner"></div>
    <p>Redirigiendo al portal de pago seguro...</p>

    {{-- Webpay requiere un formulario POST con token_ws --}}
    <form id="webpay-form" method="POST" action="{{ $webpayUrl }}">
        <input type="hidden" name="token_ws" value="{{ $webpayToken }}">
    </form>

    <script>
        document.getElementById('webpay-form').submit();
    </script>
</body>
</html>
