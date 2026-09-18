'use client';

import { useCallback, useEffect, useState } from 'react';
import { API_URL, VAPID_PUBLIC_KEY } from '../lib/config';

// Suscripcion a Web Push del lado del navegador - mismo endpoint que usa
// el panel de Filament del lado admin/supervisor (POST/DELETE
// /api/v1/push-subscriptions, autenticado). Nada se activa solo: el
// tecnico tiene que tocar el botón (ver PushNotificationsToggle.tsx),
// mismo criterio ya usado para el selector de tema (ThemeSelector.tsx) -
// "si lo toca, elige uno, si no, queda como está".
function urlBase64ToUint8Array(base64String: string): Uint8Array {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
  const rawData = atob(base64);
  const outputArray = new Uint8Array(rawData.length);
  for (let i = 0; i < rawData.length; i++) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}

export function usePushNotifications() {
  const [supported, setSupported] = useState(false);
  const [permission, setPermission] = useState<NotificationPermission>('default');
  const [subscribed, setSubscribed] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const isSupported =
      typeof window !== 'undefined' &&
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window &&
      !!VAPID_PUBLIC_KEY;

    setSupported(isSupported);
    if (!isSupported) return;

    setPermission(Notification.permission);

    navigator.serviceWorker.ready
      .then((registration) => registration.pushManager.getSubscription())
      .then((sub) => setSubscribed(!!sub))
      .catch(() => setSubscribed(false));
  }, []);

  const subscribe = useCallback(async () => {
    if (!supported) return;
    setLoading(true);
    setError(null);

    try {
      const permissionResult = await Notification.requestPermission();
      setPermission(permissionResult);

      if (permissionResult !== 'granted') {
        setError('Permiso de notificaciones denegado');
        setLoading(false);
        return;
      }

      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        // Cast necesario: el tipo de PushManager.subscribe() en el lib.dom
        // que trae este Next/TS espera BufferSource con buffer: ArrayBuffer
        // estricto, pero Uint8Array.buffer tipa como ArrayBufferLike
        // (incluye SharedArrayBuffer) - el objeto real en runtime es
        // correcto, es un desajuste de tipos de TS, no un bug real.
        applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY) as BufferSource,
      });

      const token = localStorage.getItem('token');
      const json = subscription.toJSON();

      await fetch(`${API_URL}/api/v1/push-subscriptions`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          endpoint: json.endpoint,
          keys: json.keys,
        }),
      });

      setSubscribed(true);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Error al suscribirse a notificaciones');
    } finally {
      setLoading(false);
    }
  }, [supported]);

  const unsubscribe = useCallback(async () => {
    if (!supported) return;
    setLoading(true);
    setError(null);

    try {
      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();

      if (subscription) {
        const endpoint = subscription.endpoint;
        await subscription.unsubscribe();

        const token = localStorage.getItem('token');
        await fetch(`${API_URL}/api/v1/push-subscriptions`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({ endpoint }),
        });
      }

      setSubscribed(false);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Error al cancelar la suscripción');
    } finally {
      setLoading(false);
    }
  }, [supported]);

  return { supported, permission, subscribed, loading, error, subscribe, unsubscribe };
}
