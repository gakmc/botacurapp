if (location.protocol !== 'https:') {
    alert('Debes usar HTTPS para activar notificaciones');
    return;
}

self.addEventListener('push', function (event) {
    let data = {};

    try {
        data = event.data.json();
    } catch (e) {
        data = {
            title: 'Botacura',
            body: 'Pedido listo para entrega',
            url: self.location.origin + '/barman/bebidas'
        };
    }

    const options = {
        body: data.body,
        icon: '/icons/icon-192.png',
        badge: '/icons/badge-72.png',
        data: { url: data.url },
        vibrate: [200, 100, 200],
        requireInteraction: true
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    event.waitUntil(
        clients.openWindow(event.notification.data.url)
    );
});