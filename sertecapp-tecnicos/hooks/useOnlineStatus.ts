'use client';

import { useState, useEffect, useRef } from 'react';
import { API_URL } from '../lib/config';

export function useOnlineStatus() {
  const [isOnline, setIsOnline] = useState(true);
  const [backendOnline, setBackendOnline] = useState(true);
  const [forceOffline, setForceOffline] = useState(false);
  const failCount = useRef(0);

  useEffect(() => {
    // forceOffline solo dura la sesión — NO persiste entre recargas
    const saved = sessionStorage.getItem('forceOffline');
    if (saved === 'true') setForceOffline(true);
    // Limpiar el viejo localStorage si quedó
    localStorage.removeItem('forceOffline');

    const updateOnlineStatus = () => setIsOnline(navigator.onLine);
    updateOnlineStatus();
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    const checkBackend = async () => {
      // Si el browser dice offline, no intentamos ping
      if (!navigator.onLine) {
        setBackendOnline(false);
        failCount.current = 0;
        return;
      }
      try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 5000);
        const response = await fetch(`${API_URL}/api/health`, {
          signal: controller.signal,
          cache: 'no-store',
        });
        clearTimeout(timeoutId);
        if (response.ok) {
          failCount.current = 0;
          setBackendOnline(true);
        } else {
          failCount.current++;
          if (failCount.current >= 3) setBackendOnline(false);
        }
      } catch {
        failCount.current++;
        // Solo marcar offline después de 3 fallos consecutivos
        if (failCount.current >= 3) {
          setBackendOnline(false);
        }
      }
    };

    checkBackend();
    const interval = setInterval(checkBackend, 20000);

    return () => {
      window.removeEventListener('online', updateOnlineStatus);
      window.removeEventListener('offline', updateOnlineStatus);
      clearInterval(interval);
    };
  }, []);

  const toggleForceOffline = () => {
    const newState = !forceOffline;
    setForceOffline(newState);
    sessionStorage.setItem('forceOffline', newState.toString());
  };

  const effectiveOnline = forceOffline ? false : (isOnline && backendOnline);

  return {
    isOnline: effectiveOnline,
    forceOffline,
    toggleForceOffline,
    realOnline: isOnline,
    backendOnline,
  };
}
