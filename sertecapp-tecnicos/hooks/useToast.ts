'use client';

import { useState, useCallback } from 'react';

export interface ToastData {
  id: string;
  message: string;
  type: 'success' | 'error' | 'info' | 'loading';
}

export const useToast = () => {
  const [toasts, setToasts] = useState<ToastData[]>([]);

  const showToast = useCallback((message: string, type: ToastData['type'] = 'info') => {
    const id = Date.now().toString();
    setToasts(prev => [...prev, { id, message, type }]);
    return id;
  }, []);

  const hideToast = useCallback((id: string) => {
    setToasts(prev => prev.filter(t => t.id !== id));
  }, []);

  const updateToast = useCallback((id: string, message: string, type: ToastData['type']) => {
    setToasts(prev => prev.map(t => t.id === id ? { ...t, message, type } : t));
  }, []);

  return {
    toasts,
    showToast,
    hideToast,
    updateToast,
  };
};
