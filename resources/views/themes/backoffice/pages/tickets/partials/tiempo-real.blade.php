{{--
    Tiempo real (Echo + canales privados de routes/channels.php).
    Parámetro: supervision (bool) → escucha todos los departamentos en vez de solo los del usuario.
    Uso: tkAlCambiarTicket(function (e) { ... }); e = { ticketId, tipo, userId } o null si el
    socket se reconectó (pudo perderse algún aviso mientras estuvo caído).
--}}
@php
    $canalesTk = !empty($supervision)
        ? ['tickets.supervision']
        : Auth::user()->rolesTickets()->pluck('id')->map(function ($id) { return 'tickets.rol.' . $id; })->all();
@endphp
<script>
(function () {
    var canales = @json($canalesTk), yo = @json(Auth::id());

    window.tkAlCambiarTicket = function (alCambiar) {
        // app.js (Echo) se carga después de esta vista
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.Echo || !canales.length) return;

            var conectadoAntes = false;
            window.Echo.connector.pusher.connection.bind('connected', function () {
                if (conectadoAntes) alCambiar(null);
                conectadoAntes = true;
            });

            canales.forEach(function (canal) {
                window.Echo.private(canal).listen('.ticket.actualizado', function (e) {
                    // Quien hizo el cambio ya ve el resultado en su pantalla
                    if (e.userId && Number(e.userId) === Number(yo)) return;
                    alCambiar(e);
                });
            });
        });
    };

    // Aviso con botón para actualizar, cuando recargar solo interrumpiría al usuario
    window.tkAvisarCambio = function (texto) {
        var html = '<span>' + texto + '</span><a class="btn-flat toast-action yellow-text" href="#!" onclick="location.reload();return false;">Actualizar</a>';
        if (window.M && M.toast) M.toast({ html: html, displayLength: 15000 });
        else if (window.Materialize && Materialize.toast) Materialize.toast(html, 15000);
    };
})();
</script>
