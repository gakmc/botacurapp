<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" href="{{ asset('images/logo/icono.png') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Sesión expirada | Botacura</title>
    <style>
        :root {
            --verde: #0e9a72;
            --verde-oscuro: #0b7a5a;
            --verde-suave: #e7f5f0;
            --texto: #1f2d2a;
            --texto-suave: #5f6f6b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            color: var(--texto);
            background: linear-gradient(135deg, #f4faf8 0%, #dff1ea 100%);
        }

        .tarjeta {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px 32px;
            text-align: center;
            box-shadow: 0 20px 45px rgba(14, 154, 114, .12), 0 2px 6px rgba(0, 0, 0, .04);
            animation: aparecer .45s ease-out;
        }

        .logo {
            width: 120px;
            margin: -8px auto 8px;
            display: block;
        }

        .icono {
            width: 76px;
            height: 76px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: var(--verde-suave);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icono svg { width: 38px; height: 38px; stroke: var(--verde); }

        h1 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        p {
            font-size: 14.5px;
            line-height: 1.6;
            color: var(--texto-suave);
            margin-bottom: 28px;
        }

        .boton {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 20px;
            border-radius: 10px;
            background: var(--verde);
            color: #fff;
            font-size: 15px;
            font-weight: 500;
            text-decoration: none;
            transition: background .2s, transform .1s;
        }

        .boton:hover { background: var(--verde-oscuro); }
        .boton:active { transform: scale(.98); }
        .boton svg { width: 18px; height: 18px; stroke: currentColor; }

        .pie {
            margin-top: 22px;
            font-size: 12px;
            color: #9aa8a4;
        }

        @keyframes aparecer {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body>
    <main class="tarjeta">
        <img class="logo" src="{{ asset('images/logo/botacura-logo-green.png') }}" alt="Botacura">

        <div class="icono">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" />
                <polyline points="12 7 12 12 15 14" />
            </svg>
        </div>

        <h1>Tu sesión ha expirado</h1>
        <p>
            Por seguridad, cerramos tu sesión tras un periodo de inactividad.
            Inicia sesión nuevamente para continuar donde lo dejaste.
        </p>

        <a href="{{ route('login') }}" class="boton">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                <polyline points="10 17 15 12 10 7" />
                <line x1="15" y1="12" x2="3" y2="12" />
            </svg>
            Ir al inicio de sesión
        </a>

        <div class="pie">Botacura &middot; Cajón del Maipo</div>
    </main>
</body>

</html>
