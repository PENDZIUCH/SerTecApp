'use client';

import { useEffect, useState } from 'react';

type Theme = 'light' | 'dark' | 'system';

export const useDarkMode = () => {
  const [theme, setTheme] = useState<Theme>('system');
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    const saved = localStorage.getItem('theme') as Theme;

    if (saved === 'dark' || saved === 'light') {
      setTheme(saved);
      applyTheme(saved);
    } else {
      // Sin preferencia guardada o 'system' — detectar el sistema
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const resolved: Theme = prefersDark ? 'dark' : 'light';
      setTheme(resolved);
      applyTheme(resolved);
    }

    // Listener para cambios del sistema (solo si no hay preferencia manual)
    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    const handleChange = () => {
      const current = localStorage.getItem('theme');
      if (!current || current === 'system') {
        const resolved: Theme = mediaQuery.matches ? 'dark' : 'light';
        setTheme(resolved);
        applyTheme(resolved);
      }
    };
    mediaQuery.addEventListener('change', handleChange);
    return () => mediaQuery.removeEventListener('change', handleChange);
  }, []);

  const applyTheme = (newTheme: Theme) => {
    const root = document.documentElement;
    root.classList.remove('light', 'dark');
    if (newTheme === 'dark') root.classList.add('dark');
  };

  const changeTheme = (newTheme: Theme) => {
    setTheme(newTheme);
    localStorage.setItem('theme', newTheme);
    applyTheme(newTheme);
  };

  return { theme, changeTheme, mounted };
};
