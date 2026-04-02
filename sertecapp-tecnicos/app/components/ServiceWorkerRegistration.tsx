'use client';

import { useEffect } from 'react';

export function ServiceWorkerRegistration() {
  useEffect(() => {
    if (!('serviceWorker' in navigator)) return;

    navigator.serviceWorker.register('/sw.js').then((registration) => {
      console.log('SW registrado:', registration.scope);
      // Chequear actualizaciones al abrir la app
      registration.update();

      // Cuando hay un nuevo SW listo, lo activamos silenciosamente
      // SIN recargar la página — evita deslogueos inesperados
      registration.addEventListener('updatefound', () => {
        const newWorker = registration.installing;
        if (!newWorker) return;
        newWorker.addEventListener('statechange', () => {
          if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
            // Activar el nuevo SW en silencio — se usará en la próxima carga normal
            newWorker.postMessage({ type: 'SKIP_WAITING' });
            console.log('SW actualizado silenciosamente');
          }
        });
      });
    }).catch((e) => console.error('SW registration failed:', e));

    // ELIMINADO: el controllerchange que hacía window.location.reload()
    // Ese reload forzado causaba deslogueos inesperados al reconectar
  }, []);

  return null;
}
