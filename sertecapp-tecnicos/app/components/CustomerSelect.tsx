'use client';

import { useState, useEffect, useRef } from 'react';
import { API_URL } from '../../lib/config';

interface Customer { id: number; business_name: string | null; full_name: string; }

interface Props {
  token: string;
  value: string;
  initialName?: string;
  onChange: (id: string) => void;
  placeholder?: string;
}

export function CustomerSelect({ token, value, initialName, onChange, placeholder = 'Buscar cliente...' }: Props) {
  const [query, setQuery] = useState('');
  const [results, setResults] = useState<Customer[]>([]);
  const [open, setOpen] = useState(false);
  const [selectedName, setSelectedName] = useState(initialName || '');
  const [loading, setLoading] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  // Cerrar al hacer click fuera
  useEffect(() => {
    const handler = (e: MouseEvent) => { if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false); };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, []);

  // Buscar con debounce
  useEffect(() => {
    if (!open) return;
    const timer = setTimeout(async () => {
      setLoading(true);
      try {
        const params = new URLSearchParams({ per_page: '15' });
        if (query) params.set('search', query);
        const res = await fetch(`${API_URL}/api/v1/customers?${params}`, {
          headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
        });
        if (res.ok) { const d = await res.json(); setResults(d.data || []); }
      } catch {} finally { setLoading(false); }
    }, 300);
    return () => clearTimeout(timer);
  }, [query, open, token]);

  const select = (c: Customer) => {
    const name = c.business_name || c.full_name || '';
    setSelectedName(name);
    onChange(String(c.id));
    setOpen(false);
    setQuery('');
  };

  const displayName = (c: Customer) => c.business_name || c.full_name || '';

  return (
    <div ref={ref} className="relative">
      <div
        className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white cursor-pointer flex items-center justify-between"
        onClick={() => setOpen(o => !o)}
      >
        <span className={value ? 'text-gray-900' : 'text-gray-400'}>
          {value ? selectedName || 'Cliente seleccionado' : placeholder}
        </span>
        <span className="text-gray-400 text-xs">▼</span>
      </div>
      {open && (
        <div className="absolute z-50 w-full bg-white border border-gray-200 rounded-xl shadow-xl mt-1 overflow-hidden">
          <div className="p-2 border-b border-gray-100">
            <input
              autoFocus
              value={query}
              onChange={e => setQuery(e.target.value)}
              placeholder="Escribí para filtrar..."
              className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:outline-none"
            />
          </div>
          <div className="max-h-48 overflow-y-auto">
            {loading ? (
              <p className="text-center text-sm text-gray-400 py-4">Buscando...</p>
            ) : results.length === 0 ? (
              <p className="text-center text-sm text-gray-400 py-4">Sin resultados</p>
            ) : results.map(c => (
              <div
                key={c.id}
                onClick={() => select(c)}
                className={`px-4 py-2.5 text-sm cursor-pointer hover:bg-red-50 hover:text-red-700 ${String(c.id) === value ? 'bg-red-50 text-red-700 font-medium' : 'text-gray-800'}`}
              >
                {displayName(c)}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
