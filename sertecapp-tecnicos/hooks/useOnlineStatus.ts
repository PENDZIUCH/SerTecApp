'use client';

import { useState, useEffect, useRef } from 'react';
import { API_URL } from '../lib/config';

export function useOnlineStatus() {
  const [isOnline, setIsOnline] = useState(true);
  const [backendOnline, setBackendOnline] = useState(true);
  const [forceOffline, setForceOffline] = useState(false);
  const failCount = useRef(0);

  useEffect(() => {
    // forceOffline solo dura la sesión actual
    const saved = sessionStorage.getItem('forceOffline');
    if (saved === 'true') setForceOffline(true);
    localStorage.removeItem('forceOffline');

    const updateOnlineStatus = () => setIsOnline(navigator.onLine);
    updateOnlineStatus();
    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    const checkBackend = async () => {
      if (!navigator.onLine) {
        setBackendOnline(false);
        failCount.current = 0;
        return;
      }
      try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 8000);
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
        if (failCount.current >= 3) setBackendOnline(false);
      }
    };

    // Primer check con delay de 3s — deja que la app cargue primero
    const firstCheck = setTimeout(checkBackend, 3000);
    // Checks periódicos cada 30s — menos agresivo
    const interval = setInterval(checkBackend, 30000);

    return () => {
      clearTimeout(firstCheck);
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

  // Online efectivo = navegador online Y backend online Y no forzado offline
  const effectiveOnline = forceOffline ? false : (isOnline && backendOnline);

  return {
    isOnline: effectiveOnline,
    forceOffline,
    toggleForceOffline,
    realOnline: isOnline,
    backendOnline,
  };
}
