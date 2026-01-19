'use client';

import { useState, useEffect } from 'react';

export function useOnlineStatus() {
  const [isOnline, setIsOnline] = useState(true);
  const [backendOnline, setBackendOnline] = useState(true);
  const [forceOffline, setForceOffline] = useState(false);

  useEffect(() => {
    // Leer estado guardado
    const saved = localStorage.getItem('forceOffline');
    if (saved === 'true') {
      setForceOffline(true);
    }

    // Detectar conexión real del navegador
    const updateOnlineStatus = () => {
      setIsOnline(navigator.onLine);
    };

    updateOnlineStatus();

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);

    // Ping al backend cada 10 segundos para verificar si está vivo
    const checkBackend = async () => {
      try {
        const apiUrl = 'https://sertecapp.pendziuch.com';
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);
        
        const response = await fetch(`${apiUrl}/api/health`, {
          signal: controller.signal,
          cache: 'no-store'
        });
        
        clearTimeout(timeoutId);
        setBackendOnline(response.ok);
      } catch (error) {
        setBackendOnline(false);
      }
    };

    // Check inmediato
    checkBackend();
    
    // Check periódico
    const interval = setInterval(checkBackend, 10000);

    return () => {
      window.removeEventListener('online', updateOnlineStatus);
      window.removeEventListener('offline', updateOnlineStatus);
      clearInterval(interval);
    };
  }, []);

  const toggleForceOffline = () => {
    const newState = !forceOffline;
    setForceOffline(newState);
    localStorage.setItem('forceOffline', newState.toString());
  };

  // Efectivamente online = navegador online Y backend online (a menos que force offline esté activo)
  const effectiveOnline = forceOffline ? false : (isOnline && backendOnline);

  return {
    isOnline: effectiveOnline,
    forceOffline,
    toggleForceOffline,
    realOnline: isOnline,
    backendOnline,
  };
}
