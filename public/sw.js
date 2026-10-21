/* Service Worker for web push notifications. */

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', (event) => {
    let payload = {
        title: 'New novedad',
        body: 'You have a new novedad waiting.',
        url: '/',
        tag: 'novedad',
    };

    if (event.data) {
        try {
            payload = { ...payload, ...event.data.json() };
        } catch (error) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        tag: payload.tag,
        data: { url: payload.url },
        renotify: false,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            for (const client of clientList) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }

            return undefined;
        }),
    );
});
