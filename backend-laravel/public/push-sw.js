// Service worker liviano, SOLO para Web Push - Filament no es una PWA (no
// tiene caching offline ni nada parecido) asi que a diferencia de
// sertecapp-tecnicos (que ya tiene su propio sw.js generado por
// next-pwa/workbox) este archivo ES el service worker completo, no se
// inyecta en nada. Registrado desde
// resources/views/filament/widgets/push-notifications-widget.blade.php.
// Mismos dos listeners que public-worker.js del lado PWA (ver
// sertecapp-tecnicos/public/push-worker.js) - logica identica a proposito,
// las dos audiencias (tecnicos / supervisores-admins) reciben el mismo
// tipo de payload desde el mismo backend (App\Notifications\PushNotification).
self.addEventListener('push', function (event) {
  if (!event.data) return;

  let payload = {};
  try {
    payload = event.data.json();
  } catch (e) {
    payload = { title: 'SerTecApp', body: event.data.text() };
  }

  const title = payload.title || 'SerTecApp';
  const options = {
    body: payload.body || '',
    icon: payload.icon || '/favicon.ico',
    badge: payload.badge || '/favicon.ico',
    data: payload.data || {},
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  const url = (event.notification.data && event.notification.data.url) || '/sertecapp';

  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
      for (const client of clientList) {
        if (client.url.includes(url) && 'focus' in client) {
          return client.focus();
        }
      }
      if (self.clients.openWindow) {
        return self.clients.openWindow(url);
      }
    })
  );
});
