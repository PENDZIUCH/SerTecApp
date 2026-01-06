'use client';

import { useState, useEffect } from 'react';

export function useOnlineStatus() {
  const [isOnline, setIsOnline] = useState(true);
  const [forceOffline, setForceOffline] = useState(false);

  useEffect(() => {
    // Leer estado guardado
    const saved = localStorage.getItem('forceOffline');
    if (saved === 'true') {
      setForceOffline(true);
    }

    // Detectar conexión real
    const updateOnlineStatus = () => {
      setIsOnline(navigator.onLine);
    };

    updateOnlineStatus();

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    return () => {
      window.removeEventListener('online', updateOnlineStatus);
      window.removeEventListener('offline', updateOnlineStatus);
    };
  }, []);

  const toggleForceOffline = () => {
    const newState = !forceOffline;
    setForceOffline(newState);
    localStorage.setItem('forceOffline', newState.toString());
  };

  const effectiveOnline = forceOffline ? false : isOnline;

  return {
    isOnline: effectiveOnline,
    forceOffline,
    toggleForceOffline,
    realOnline: isOnline,
  };
}
