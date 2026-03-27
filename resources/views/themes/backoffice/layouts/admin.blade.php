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
        <script src="{{ asset('js/app.js') }}"></script>
        {{-- <script src="{{ mix('js/app.js') }}"></script> --}}

{{-- <script>console.log('Como?')</script> --}}
    <script>
        async function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const rawData = window.atob(base64);
            return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
        }

        async function activarNotificacionesPush() {

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') return;

            const registration = await navigator.serviceWorker.register('/sw.js');

            let subscription = await registration.pushManager.getSubscription();

            if (!subscription) {
                const vapidKey = document.querySelector('meta[name="vapid-public-key"]').content;

                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: await urlBase64ToUint8Array(vapidKey)
                });
            }

            await fetch('{{ route('push.subscribe') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('p256dh')))),
                        auth: btoa(String.fromCharCode(...new Uint8Array(subscription.getKey('auth'))))
                    },
                    encoding: 'aesgcm'
                })
            });
        }

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