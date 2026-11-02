{{--
    Control de expiración de sesión del backoffice.
    - Mientras el usuario trabaja (escribe, hace clic), renueva la sesión cada cierto tiempo.
    - Si estuvo inactivo y la sesión expiró, muestra esta ventana sobre la página
      para reingresar sin recargar: lo escrito en los formularios se conserva.
--}}
<div id="sesionOverlay" class="se-overlay" hidden>
    <div class="se-tarjeta" role="dialog" aria-modal="true" aria-labelledby="seTitulo">
        <div class="se-icono">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="9" />
                <polyline points="12 7 12 12 15 14" />
            </svg>
        </div>

        <h2 id="seTitulo">Tu sesión ha expirado</h2>
        <p class="se-texto">
            No te preocupes, lo que estabas haciendo sigue aquí.
            Ingresa tu contraseña para continuar.
        </p>

        <div id="seError" class="se-error" hidden></div>

        <form id="seForm" autocomplete="on">
            <label for="seEmail">Correo electrónico</label>
            <input id="seEmail" type="email" class="browser-default" name="email"
                value="{{ optional(auth()->user())->email }}" readonly>

            <label for="sePassword">Contraseña</label>
            <input id="sePassword" type="password" class="browser-default" name="password"
                autocomplete="current-password" required>

            <button type="submit" id="seBoton" class="se-boton">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                    <polyline points="10 17 15 12 10 7" />
                    <line x1="15" y1="12" x2="3" y2="12" />
                </svg>
                <span>Continuar</span>
            </button>
        </form>

        <a class="se-enlace" href="{{ route('login') }}">Ingresar con otra cuenta</a>
    </div>
</div>

<style>
    .se-overlay {
        position: fixed;
        inset: 0;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px 16px;
        background: linear-gradient(160deg, rgba(6, 45, 34, .78) 0%, rgba(11, 122, 90, .6) 100%);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
    }

    .se-overlay[hidden] { display: none; }

    .se-tarjeta {
        width: 100%;
        max-width: 400px;
        background: #fff;
        border-radius: 16px;
        padding: 32px 28px 24px;
        text-align: center;
        color: #1f2d2a;
        box-shadow: 0 25px 60px rgba(0, 0, 0, .35);
        animation: seAparecer .35s ease-out;
    }

    .se-icono {
        width: 68px;
        height: 68px;
        margin: 0 auto 16px;
        border-radius: 50%;
        background: #e7f5f0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .se-icono svg { width: 34px; height: 34px; stroke: #0e9a72; }

    .se-tarjeta h2 {
        font-size: 20px;
        font-weight: 600;
        line-height: 1.3;
        margin: 0 0 8px;
    }

    .se-texto {
        font-size: 14px;
        line-height: 1.6;
        color: #5f6f6b;
        margin: 0 0 20px;
    }

    .se-error {
        font-size: 13.5px;
        background: #fdecea;
        color: #c0392b;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 16px;
        text-align: left;
    }

    .se-error[hidden] { display: none; }

    #seForm { text-align: left; margin: 0; }

    #seForm label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: #1f2d2a;
        margin: 0 0 6px;
    }

    #seForm input {
        display: block;
        width: 100%;
        height: 44px;
        padding: 0 14px;
        margin: 0 0 14px;
        font-family: inherit;
        font-size: 14.5px;
        color: #1f2d2a;
        background: #fff;
        border: 1.5px solid #d9e3e0;
        border-radius: 10px;
        box-sizing: border-box;
        outline: none;
        box-shadow: none;
        transition: border-color .2s, box-shadow .2s;
    }

    #seForm input[readonly] { background: #f4f7f6; color: #5f6f6b; }

    #seForm input:focus {
        border-color: #0e9a72;
        box-shadow: 0 0 0 4px rgba(14, 154, 114, .12);
    }

    .se-boton {
        width: 100%;
        height: 46px;
        margin-top: 6px;
        border: 0;
        border-radius: 10px;
        background: #0e9a72;
        color: #fff;
        font-family: inherit;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: background .2s;
    }

    .se-boton:hover, .se-boton:focus { background: #0b7a5a; }
    .se-boton:disabled { opacity: .75; cursor: wait; }
    .se-boton svg { width: 18px; height: 18px; stroke: currentColor; }

    .se-enlace {
        display: inline-block;
        margin-top: 16px;
        font-size: 13px;
        font-weight: 500;
        color: #0e9a72;
    }

    .se-enlace:hover { text-decoration: underline; }

    @keyframes seAparecer {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    (function () {
        var URL_ESTADO = @json(route('sesion.estado'));
        var URL_LOGIN = @json(route('login'));
        var VIDA = {{ (int) config('session.lifetime') }} * 60 * 1000;
        // Cada cuánto renovar la sesión mientras el usuario está activo
        var RENOVAR = Math.min(5 * 60 * 1000, VIDA / 2);

        var ultimaRenovacion = Date.now();
        var verificando = false;
        var expirada = false;

        var overlay = document.getElementById('sesionOverlay');
        var form = document.getElementById('seForm');
        var clave = document.getElementById('sePassword');
        var boton = document.getElementById('seBoton');
        var error = document.getElementById('seError');

        var cabeceras = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };

        // Propaga el token CSRF vigente a todo lo que la página pueda enviar
        function actualizarToken(token) {
            if (!token) return;
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) meta.setAttribute('content', token);
            document.querySelectorAll('input[name="_token"]').forEach(function (i) { i.value = token; });
            if (window.jQuery) jQuery.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
            if (window.axios) window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }

        function tokenActual() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        // Consulta al servidor: si la sesión sigue activa la renueva, si no muestra la ventana
        function verificar() {
            if (verificando || expirada) return;
            verificando = true;

            fetch(URL_ESTADO, { headers: cabeceras, credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    actualizarToken(d.token);
                    if (d.activa) {
                        ultimaRenovacion = Date.now();
                    } else {
                        mostrar();
                    }
                })
                .catch(function () { /* sin conexión: se reintenta en la próxima actividad */ })
                .then(function () { verificando = false; });
        }

        function mostrar() {
            if (expirada) return;
            expirada = true;
            if (document.activeElement) document.activeElement.blur();
            error.hidden = true;
            clave.value = '';
            overlay.hidden = false;
            setTimeout(function () { clave.focus(); }, 50);
        }

        function ocultar() {
            expirada = false;
            overlay.hidden = true;
            ultimaRenovacion = Date.now();
        }

        // Actividad del usuario: renovar si ya pasó el intervalo (y detectar si expiró)
        function actividad(e) {
            if (expirada || overlay.contains(e.target)) return;
            if (Date.now() - ultimaRenovacion >= RENOVAR) verificar();
        }

        ['focusin', 'keydown', 'mousedown', 'touchstart'].forEach(function (evento) {
            document.addEventListener(evento, actividad, true);
        });

        // Inactivo: al cumplirse el tiempo de vida, confirmar con el servidor y avisar sin esperar un clic
        setInterval(function () {
            if (!expirada && Date.now() - ultimaRenovacion > VIDA + 2000) verificar();
        }, 30 * 1000);

        // Al volver a la pestaña después de un rato
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible' && Date.now() - ultimaRenovacion >= RENOVAR) verificar();
        });

        // Peticiones AJAX de la página que fallen por sesión expirada
        if (window.jQuery) {
            jQuery(document).ajaxError(function (ev, xhr) {
                if (xhr.status === 401 || xhr.status === 419) {
                    expirada = false;
                    verificar();
                }
            });
        }

        // Reingreso sin recargar la página
        function enviarLogin(reintento) {
            var datos = new FormData();
            datos.append('email', document.getElementById('seEmail').value);
            datos.append('password', clave.value);

            return fetch(URL_LOGIN, {
                method: 'POST',
                headers: Object.assign({ 'X-CSRF-TOKEN': tokenActual() }, cabeceras),
                credentials: 'same-origin',
                body: datos
            }).then(function (r) {
                // Token desactualizado: pedir uno nuevo y reintentar una vez
                if (r.status === 419 && !reintento) {
                    return fetch(URL_ESTADO, { headers: cabeceras, credentials: 'same-origin', cache: 'no-store' })
                        .then(function (e) { return e.json(); })
                        .then(function (d) { actualizarToken(d.token); return enviarLogin(true); });
                }
                return r.json().catch(function () { return {}; }).then(function (d) {
                    return { ok: r.ok, status: r.status, datos: d };
                });
            });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            error.hidden = true;
            boton.disabled = true;
            boton.querySelector('span').textContent = 'Ingresando...';

            enviarLogin(false)
                .then(function (res) {
                    if (res.ok) {
                        if (res.datos.token) actualizarToken(res.datos.token);
                        ocultar();
                        // Confirma la sesión nueva y trae el token definitivo
                        verificar();
                        return;
                    }
                    var msg = 'No se pudo iniciar sesión. Intenta nuevamente.';
                    if (res.datos.errors) {
                        var primero = Object.keys(res.datos.errors)[0];
                        msg = res.datos.errors[primero][0];
                    }
                    error.textContent = msg;
                    error.hidden = false;
                    clave.select();
                })
                .catch(function () {
                    error.textContent = 'Sin conexión con el servidor. Revisa tu conexión e intenta nuevamente.';
                    error.hidden = false;
                })
                .then(function () {
                    boton.disabled = false;
                    boton.querySelector('span').textContent = 'Continuar';
                });
        });
    })();
</script>
