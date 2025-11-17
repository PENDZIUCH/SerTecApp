'use client';

import { useState, useEffect } from 'react';

export function useDarkMode() {
  const [isDark, setIsDark] = useState(false);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    // Verificar si hay preferencia guardada
    const saved = localStorage.getItem('darkMode');
    
    if (saved !== null) {
      const darkMode = saved === 'true';
      setIsDark(darkMode);
      console.log('🌙 Dark mode loaded from localStorage:', darkMode);
    } else {
      // Detectar preferencia del sistema
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      setIsDark(prefersDark);
      console.log('🌙 Dark mode from system:', prefersDark);
    }
  }, []);

  useEffect(() => {
    if (!mounted) return;
    
    // Aplicar clase al documento
    console.log('🌙 Applying dark mode:', isDark);
    if (isDark) {
      document.documentElement.classList.add('dark');
      document.body.classList.add('dark');
      console.log('✅ Dark classes added');
    } else {
      document.documentElement.classList.remove('dark');
      document.body.classList.remove('dark');
      console.log('✅ Dark classes removed');
    }
    
    // Guardar preferencia
    localStorage.setItem('darkMode', isDark.toString());
    
    // Debug: Mostrar clases actuales
    console.log('📋 HTML classes:', document.documentElement.classList.toString());
    console.log('📋 Body classes:', document.body.classList.toString());
  }, [isDark, mounted]);

  const toggle = () => {
    console.log('🔄 Toggling dark mode from', isDark, 'to', !isDark);
    setIsDark(!isDark);
  };

  return { isDark, toggle };
}
