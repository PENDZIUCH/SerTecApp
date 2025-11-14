// SerTecApp - Service Worker para PWA
// Maneja caché y sincronización en background

const CACHE_NAME = 'sertecapp-v1';
const OFFLINE_URL = '/offline.html';

const urlsToCache = [
  '/',
  '/offline.html',
  '/manifest.json',
  '/icon-192.png',
  '/icon-512.png',
];

// Install event
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('📦 Cache abierto');
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            console.log('🗑️ Eliminando cache viejo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - Network First, Cache Fallback
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        if (!response || response.status !== 200) {
          return response;
        }
        const responseToCache = response.clone();
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });
        return response;
      })
      .catch(() => {
        return caches.match(event.request).then((response) => {
          if (response) {
            return response;
          }
          if (event.request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
          }
        });
      })
  );
});

// Background Sync - Sincronización cuando vuelve conexión
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-ordenes') {
    event.waitUntil(syncOrdenesWithBackend());
  }
});

async function syncOrdenesWithBackend() {
  const db = await openIndexedDB();
  const tx = db.transaction('sync_queue', 'readonly');
  const pendientes = await tx.objectStore('sync_queue').index('sincronizado').getAll(false);
  
  for (const item of pendientes) {
    try {
      const response = await fetch(`/api/${item.tabla}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(item.datos)
      });
      if (response.ok) {
        const txWrite = db.transaction('sync_queue', 'readwrite');
        await txWrite.objectStore('sync_queue').put({ ...item, sincronizado: true });
        console.log('✅ Item sincronizado:', item);
      }
    } catch (error) {
      console.error('❌ Error sincronizando:', error);
    }
  }
}

// Push Notifications
self.addEventListener('push', (event) => {
  const data = event.data ? event.data.json() : {};
  const title = data.title || 'SerTecApp';
  const options = {
    body: data.body || 'Nueva notificación',
    icon: '/icon-192.png',
    badge: '/icon-badge.png',
    vibrate: [200, 100, 200],
    data: data
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url || '/')
  );
});

function openIndexedDB() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('sertecapp_db', 1);
    request.onsuccess = () => resolve(request.result);
    request.onerror = () => reject(request.error);
  });
}

console.log('🚀 Service Worker SerTecApp cargado');
