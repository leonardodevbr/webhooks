self.addEventListener('push', function (event) {
    const data = event.data ? event.data.json() : {};

    const options = {
        body: data.body || 'Novo webhook recebido!',
        icon: data.icon || '/apple-touch-icon.png',
        badge: '/apple-touch-icon.png',
        tag: data.tag || '', // Adicionado para evitar substituição de notificações acidentalmente
        data: data.data || {} // Garante que "data" sempre existe
    };

    event.waitUntil(
        self.registration.showNotification(data.title || "Novo Webhook", options)
    );
});

// Abre a URL correspondente quando o usuário clica na notificação
self.addEventListener("notificationclick", function (event) {
    event.notification.close();

    const url = event.notification.data?.url;
    if (!url) return;

    event.waitUntil(
        clients.matchAll({ type: "window", includeUncontrolled: true }).then(clientList => {
            for (let client of clientList) {
                if (client.url === url && "focus" in client) {
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});
