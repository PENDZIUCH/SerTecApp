const CACHE_NAME = 'sertecapp-v1';
const OFFLINE_CACHE = 'sertecapp-offline-v1';

// Archivos críticos para funcionar offline
const STATIC_ASSETS = [
  '/',
  '/ordenes',
  '/manifest.json',
];

// Install: cachear assets estáticos
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate: limpiar caches viejos
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME && cacheName !== OFFLINE_CACHE) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch: estrategia Network-First con fallback a cache
self.addEventListener('fetch', (event) => {
  // Solo cachear GET requests
  if (event.request.method !== 'GET') return;

  event.respondWith(
    fetch(event.request)
      .then((response) => {
        // Clonar response para guardar en cache
        const responseClone = response.clone();
        
        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseClone);
        });

        return response;
      })
      .catch(() => {
        // Si falla red, buscar en cache
        return caches.match(event.request).then((cachedResponse) => {
          if (cachedResponse) {
            return cachedResponse;
          }

          // Si no hay cache, página offline
          return new Response(
            JSON.stringify({
              error: 'Sin conexión',
              message: 'Los datos se guardarán localmente y se sincronizarán cuando vuelva la conexión'
            }),
            {
              headers: { 'Content-Type': 'application/json' }
            }
          );
        });
      })
  );
});

// Background Sync: cuando vuelve conexión
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-partes') {
    event.waitUntil(syncPendingParts());
  }
});

async function syncPendingParts() {
  // TODO: Implementar sync de partes guardados localmente
  console.log('Sincronizando partes pendientes...');
}
