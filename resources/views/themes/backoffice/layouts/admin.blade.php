<!DOCTYPE html>
<html lang="es">
    <head>
    <title>@yield('title')</title>
    <link rel="shortcut icon" href="{{asset('images/logo/icono.png')}}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
        <meta name="vapid-public-key" content="{{ config('services.webpush.public_key') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('themes.backoffice.layouts.includes.head')

    </head>

    <style>
        body::-webkit-scrollbar{
            width: 10px;
            height: 10px;
        }

        body::-webkit-scrollbar-thumb{
            background: #3B82F6;
            border: 3px solid #fff;
            border-radius: 10px;
        }


    </style>
    <body>
        @include('themes.backoffice.layouts.includes.loader')
        @include('themes.backoffice.layouts.includes.header')
        <div id="main">
            <div class="wrapper">
                @include('themes.backoffice.layouts.includes.left-sidebar')
                <section id="content">
                    @include('themes.backoffice.layouts.includes.breadcrumbs')
                    <div class="container">
                        @yield('content')
                    </div>

                    
                </section>
            </div>
        </div>

        @include('themes.backoffice.layouts.includes.footer')
        {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script> --}}
        @include('themes.backoffice.layouts.includes.foot')
        @yield('foot')
        <script src="{{ mix('js/app.js') }}"></script>
        @include('themes.backoffice.layouts.includes.sesion')
        {{-- <script src="{{ asset('js/app.js') }}"></script> --}}

{{-- <script>console.log('Como?')</script> --}}
    <script>
        async function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
        }

        function enviarSuscripcionPush(ruta, datos) {
            return fetch(ruta, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(datos)
            });
        }

        // Suscripción del dispositivo con la clave VAPID actual del servidor. Si el
        // dispositivo quedó suscrito con una clave anterior (se regeneraron las claves),
        // los avisos fallarían en silencio: se cancela y se crea una nueva.
        async function obtenerSuscripcionPush(registration) {
            const clave = await urlBase64ToUint8Array(document.querySelector('meta[name="vapid-public-key"]').content);
            let subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                const actual = subscription.options && subscription.options.applicationServerKey
                    ? new Uint8Array(subscription.options.applicationServerKey) : null;
                const distinta = actual && (actual.length !== clave.length || actual.some((b, i) => b !== clave[i]));

                if (distinta) {
                    const anterior = subscription.endpoint;
                    await subscription.unsubscribe();
                    subscription = null;
                    enviarSuscripcionPush('{{ route('push.unsubscribe') }}', { endpoint: anterior }).catch(() => {});
                }
            }

            if (!subscription) {
                subscription = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: clave });
            }

            return subscription;
        }

        // Guarda la suscripción en el servidor a nombre del usuario con sesión iniciada
        function guardarSuscripcionPush(subscription) {
            return enviarSuscripcionPush('{{ route('push.subscribe') }}', {
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
                    auth: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth'))))
                },
                encoding: 'aesgcm'
            });
        }

        async function activarNotificacionesPush() {
            try {
                const esLocalhost = ['localhost', '127.0.0.1'].includes(location.hostname);
                if (location.protocol !== 'https:' && !esLocalhost) {
                    alert('Debes usar HTTPS para activar notificaciones');
                    return;
                }

                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    alert('Permiso de notificaciones no concedido (estado: ' + permission + ').');
                    return;
                }

                const registration = await navigator.serviceWorker.register('/sw.js');
                await navigator.serviceWorker.ready;

                const subscription = await obtenerSuscripcionPush(registration);
                const resp = await guardarSuscripcionPush(subscription);

                if (!resp.ok) {
                    const texto = await resp.text().catch(() => '');
                    console.error('Error al guardar la suscripcion push:', resp.status, texto);
                    alert('No se pudo guardar la suscripcion en el servidor (error ' + resp.status + '). Revisa la consola.');
                    return;
                }

                alert('Notificaciones activadas correctamente.');
            } catch (err) {
                console.error('Error activando notificaciones push:', err);
                alert('Error activando notificaciones: ' + (err && err.message ? err.message : err));
            }
        }

        // Sincronización silenciosa: si este dispositivo ya dio permiso, se revisa la clave
        // y se vuelve a guardar la suscripción a nombre del usuario actual (por si otro
        // usuario la activó en este mismo dispositivo o el servidor la borró). Se repite
        // al cambiar de usuario o de suscripción, y como mínimo cada 12 horas.
        window.addEventListener('load', async function () {
            try {
                if (!('serviceWorker' in navigator) || !('PushManager' in window) || Notification.permission !== 'granted') return;
                const registration = await navigator.serviceWorker.getRegistration('/sw.js');
                if (!registration) return;

                const subscription = await obtenerSuscripcionPush(registration);
                const firma = [@json(optional(Auth::user())->id), subscription.endpoint].join('|');
                let previa = null;
                try { previa = JSON.parse(localStorage.getItem('push-sync') || 'null'); } catch (e) {}
                if (previa && previa.firma === firma && Date.now() - previa.hora < 12 * 3600 * 1000) return;

                const resp = await guardarSuscripcionPush(subscription);
                if (resp.ok) {
                    try { localStorage.setItem('push-sync', JSON.stringify({ firma: firma, hora: Date.now() })); } catch (e) {}
                }
            } catch (err) {
                console.warn('No se pudo sincronizar la suscripción push:', err);
            }
        });

    </script>

    <script>
document.addEventListener('DOMContentLoaded', function () {

    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.getRegistration().then(async reg => {
        if (!reg) return;

        const sub = await reg.pushManager.getSubscription();

        if (sub) {
            const btnDesktop = document.getElementById('btnPushDesktop');
            const btnFab     = document.getElementById('btnPushFab');

            if (btnDesktop) btnDesktop.style.display = 'none';
            if (btnFab) btnFab.style.display = 'none';
        }
    });

});
</script>
    </body>
</html>