'use client';

import { useEffect } from 'react';

export function ServiceWorkerRegistration() {
  useEffect(() => {
    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.register('/sw.js').then((registration) => {
      console.log('SW registrado:', registration.scope);

      // Forzar chequeo de actualización cada vez que carga la app
      registration.update();

      // Cuando hay un nuevo SW esperando, tomarlo inmediatamente
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        if (!newWorker) return;
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            console.log('Nueva versión disponible — recargando...');
            // Avisar al SW que tome control ya
            newWorker.postMessage({ type: 'SKIP_WAITING' });
          }
        });
      });
    }).catch((e) => console.error('SW registration failed:', e));

    // Cuando el SW toma control, recargar la página para usar la versión nueva
    let refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (!refreshing) {
        refreshing = true;
        window.location.reload();
      }
    });
  }, []);

  return null;
}
