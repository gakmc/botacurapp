<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" href="{{ asset('images/logo/icono.png') }}" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <title>Iniciar sesión | Botacura</title>
    <style>
        :root {
            --verde: #0e9a72;
            --verde-oscuro: #0b7a5a;
            --verde-suave: #e7f5f0;
            --texto: #1f2d2a;
            --texto-suave: #5f6f6b;
            --borde: #d9e3e0;
            --error: #c0392b;
            --error-suave: #fdecea;
            --aviso: #8a6d1d;
            --aviso-suave: #fff6dc;
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
            background: #0b3d2e url("{{ asset('images/botacura/login-fondo.jpg') }}") center / cover no-repeat fixed;
            position: relative;
        }

        /* Velo verde sobre la foto: la imagen acompaña, el formulario destaca */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(160deg, rgba(6, 45, 34, .82) 0%, rgba(11, 122, 90, .62) 100%);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
        }

        .tarjeta {
            position: relative;
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            padding: 36px 32px 28px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, .35);
            animation: aparecer .45s ease-out;
        }

        .logo {
            width: 130px;
            display: block;
            margin: -12px auto 0;
        }

        h1 {
            font-size: 21px;
            font-weight: 600;
            text-align: center;
            margin-top: 4px;
        }

        .subtitulo {
            font-size: 14px;
            color: var(--texto-suave);
            text-align: center;
            margin: 4px 0 24px;
        }

        .alerta {
            font-size: 13.5px;
            line-height: 1.5;
            border-radius: 10px;
            padding: 11px 14px;
            margin-bottom: 18px;
        }

        .alerta-error { background: var(--error-suave); color: var(--error); }
        .alerta-aviso { background: var(--aviso-suave); color: var(--aviso); }

        .campo { margin-bottom: 16px; }

        label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .entrada {
            position: relative;
        }

        .entrada > svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            stroke: #9aa8a4;
            pointer-events: none;
        }

        input[type="email"],
        input[type="password"],
        input[type="text"] {
            width: 100%;
            height: 46px;
            padding: 0 44px 0 42px;
            font-family: inherit;
            font-size: 14.5px;
            color: var(--texto);
            border: 1.5px solid var(--borde);
            border-radius: 10px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus {
            border-color: var(--verde);
            box-shadow: 0 0 0 4px rgba(14, 154, 114, .12);
        }

        input.invalido { border-color: var(--error); }

        .ver-clave {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border: 0;
            background: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
        }

        .ver-clave:hover { background: var(--verde-suave); }
        .ver-clave svg { width: 18px; height: 18px; stroke: var(--texto-suave); }

        .opciones {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 13px;
            margin: 4px 0 22px;
        }

        .recordar {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--texto-suave);
        }

        .recordar input { width: 16px; height: 16px; accent-color: var(--verde); cursor: pointer; }

        .enlace {
            color: var(--verde);
            text-decoration: none;
            font-weight: 500;
        }

        .enlace:hover { text-decoration: underline; }

        .boton {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 10px;
            background: var(--verde);
            color: #fff;
            font-family: inherit;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background .2s, transform .1s;
        }

        .boton:hover { background: var(--verde-oscuro); }
        .boton:active { transform: scale(.98); }
        .boton:disabled { opacity: .75; cursor: wait; }
        .boton svg { width: 18px; height: 18px; stroke: currentColor; }

        .girando { animation: girar .8s linear infinite; }

        .pie {
            margin-top: 22px;
            font-size: 12px;
            color: #9aa8a4;
            text-align: center;
        }

        @keyframes aparecer {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes girar { to { transform: rotate(360deg); } }

        @media (max-width: 420px) {
            .tarjeta { padding: 28px 22px 22px; }
        }
    </style>
</head>

<body>
    <main class="tarjeta">
        <img class="logo" src="{{ asset('images/logo/botacura-logo-green.png') }}" alt="Botacura">

        <h1>Bienvenido</h1>
        <p class="subtitulo">Ingresa tus credenciales para continuar</p>

        @if (session('status'))
            <div class="alerta alerta-aviso" role="alert">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alerta alerta-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form id="loginForm" method="POST" action="{{ route('login') }}">
            @csrf

            <div class="campo">
                <label for="email">Correo electrónico</label>
                <div class="entrada">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <polyline points="3 7 12 13 21 7" />
                    </svg>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                        class="@error('email') invalido @enderror" placeholder="nombre@botacura.cl"
                        required autocomplete="email" autofocus>
                </div>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <div class="entrada">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="11" width="16" height="10" rx="2" />
                        <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                    </svg>
                    <input id="password" type="password" name="password"
                        class="@error('password') invalido @enderror" placeholder="••••••••"
                        required autocomplete="current-password">
                    <button type="button" class="ver-clave" id="verClave" aria-label="Mostrar contraseña">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="opciones">
                <label class="recordar" for="remember">
                    <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    Recordarme
                </label>

                @if (Route::has('password.request'))
                    <a class="enlace" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
                @endif
            </div>

            <button type="submit" class="boton" id="btnIngresar">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <polyline points="10 17 15 12 10 7" />
                    <line x1="15" y1="12" x2="3" y2="12" />
                </svg>
                <span>Iniciar sesión</span>
            </button>
        </form>

        <div class="pie">Botacura &middot; Cajón del Maipo</div>
    </main>

    <script>
        // Mostrar / ocultar contraseña
        document.getElementById('verClave').addEventListener('click', function () {
            var clave = document.getElementById('password');
            var visible = clave.type === 'text';
            clave.type = visible ? 'password' : 'text';
            this.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
        });

        // Evitar doble envío y mostrar que está procesando
        document.getElementById('loginForm').addEventListener('submit', function () {
            var boton = document.getElementById('btnIngresar');
            boton.disabled = true;
            boton.querySelector('svg').outerHTML =
                '<svg class="girando" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round"><path d="M12 3a9 9 0 1 0 9 9" /></svg>';
            boton.querySelector('span').textContent = 'Ingresando...';
        });
    </script>
</body>

</html>
