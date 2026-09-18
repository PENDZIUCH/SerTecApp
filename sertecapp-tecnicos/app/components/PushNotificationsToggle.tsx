'use client';

import { usePushNotifications } from '../../hooks/usePushNotifications';

// Boton simple para activar/desactivar notificaciones push del navegador -
// mismo criterio que ThemeSelector.tsx: no se fuerza nada automatico, el
// tecnico decide. Si el navegador no soporta Web Push (o no hay
// NEXT_PUBLIC_VAPID_PUBLIC_KEY seteada en el build) no se muestra nada.
export function PushNotificationsToggle() {
  const { supported, permission, subscribed, loading, error, subscribe, unsubscribe } = usePushNotifications();

  if (!supported) return null;

  if (permission === 'denied') {
    return (
      <div>
        <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Notificaciones</p>
        <p className="text-xs text-gray-400 dark:text-gray-500">
          Bloqueadas en el navegador. Habilitalas desde la configuración del sitio para activarlas.
        </p>
      </div>
    );
  }

  return (
    <div>
      <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Notificaciones</p>
      <button
        onClick={() => (subscribed ? unsubscribe() : subscribe())}
        disabled={loading}
        className={`w-full py-2 px-3 rounded-lg text-sm font-medium transition-all disabled:opacity-50 ${
          subscribed
            ? 'bg-green-600 text-white shadow-md hover:bg-green-700'
            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
        }`}
      >
        {loading ? 'Procesando...' : subscribed ? '🔔 Activadas' : '🔕 Activar Notificaciones'}
      </button>
      {error && <p className="text-xs text-red-500 mt-1">{error}</p>}
    </div>
  );
}
