'use client';

import { useEffect, useState } from 'react';

type Theme = 'light' | 'dark' | 'system';

export const useDarkMode = () => {
  const [theme, setTheme] = useState<Theme>('system');
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    const saved = localStorage.getItem('theme') as Theme | null;

    if (saved === 'dark' || saved === 'light') {
      setTheme(saved);
      applyTheme(saved);
    } else {
      // Sin preferencia guardada o 'system' - el estado se queda en
      // 'system' (asi el boton "Automático" se ve marcado como activo),
      // pero la CLASE aplicada al documento sí se resuelve a claro/oscuro
      // real, vía applyTheme.
      setTheme('system');
      applyTheme('system');
    }

    // Listener para cambios del sistema (solo si el modo elegido es 'system')
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handleChange = () => {
      const current = localStorage.getItem('theme');
      if (!current || current === 'system') {
        applyTheme('system');
      }
    };
    mediaQuery.addEventListener('change', handleChange);
    return () => mediaQuery.removeEventListener('change', handleChange);
  }, []);

  const applyTheme = (newTheme: Theme) => {
    const root = document.documentElement;
    root.classList.remove('light', 'dark');
    // 'system' se resuelve ACA contra la preferencia real del dispositivo
    // en este momento - antes quedaba en claro hasta el proximo cambio de
    // sistema, porque solo miraba newTheme === 'dark'. No se notaba antes
    // porque nada llamaba a changeTheme('system') todavia (no habia botón).
    const isDark = newTheme === 'dark' ||
      (newTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    if (isDark) root.classList.add('dark');
  };

  const changeTheme = (newTheme: Theme) => {
    setTheme(newTheme);
    localStorage.setItem('theme', newTheme);
    applyTheme(newTheme);
  };

  return { theme, changeTheme, mounted };
};
