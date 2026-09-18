// Listeners de Web Push, separados del service worker principal que genera
// next-pwa/workbox (public/sw.js, gitignoreado - se regenera en cada build,
// ver .gitignore y CLAUDE.md sesion 2026-09-04). Este archivo SI se
// versiona: se inyecta dentro del sw.js generado via `importScripts` (ver
// next.config.ts, opcion `importScripts: ['/push-worker.js']` de
// next-pwa/workbox) - asi el caching que ya arma next-pwa no se toca para
// nada, solo se le agregan estos dos listeners nuevos.
/* eslint-disable no-undef */

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
    icon: payload.icon || '/icon-192.svg',
    badge: payload.badge || '/icon-192.svg',
    data: payload.data || {},
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();

  const url = (event.notification.data && event.notification.data.url) || '/ordenes';

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
