'use client';

import { useDarkMode } from '../../hooks/useDarkMode';

// Selector de tema compartido entre técnico (Mis Órdenes) y admin - un
// solo lugar, no una copia por pantalla (ver PENDIENTES.md sobre el
// riesgo de tener pantallas duplicadas que se desincronizan). Usa el
// mismo hook useDarkMode ya probado - Automático sigue al sistema en
// vivo, Claro/Oscuro quedan fijos hasta que se elija otra opción.
export function ThemeSelector() {
  const { theme, changeTheme } = useDarkMode();

  const opciones: { value: 'light' | 'dark' | 'system'; label: string; icon: string }[] = [
    { value: 'light', label: 'Claro', icon: '☀️' },
    { value: 'dark', label: 'Oscuro', icon: '🌙' },
    { value: 'system', label: 'Automático', icon: '🖥️' },
  ];

  return (
    <div>
      <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Tema</p>
      <div className="flex gap-1">
        {opciones.map((o) => (
          <button
            key={o.value}
            onClick={() => changeTheme(o.value)}
            className={`flex-1 py-2 px-2 rounded-lg text-sm font-medium transition-all ${
              theme === o.value
                ? o.value === 'dark'
                  ? 'bg-gray-900 text-white shadow-md'
                  : o.value === 'light'
                    ? 'bg-yellow-500 text-white shadow-md'
                    : 'bg-blue-600 text-white shadow-md'
                : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
            }`}
          >
            {o.icon} {o.label}
          </button>
        ))}
      </div>
    </div>
  );
}
