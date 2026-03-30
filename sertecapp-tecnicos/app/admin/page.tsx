'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';

const API = 'https://sertecapp.pendziuch.com';

export default function AdminPage() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [stats, setStats] = useState({ ordenes: 0, clientes: 0, repuestos: 0, tecnicos: 0 });
  const [ordenes, setOrdenes] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const token = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!token || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    const roles: string[] = u?.roles || [];
    if (!roles.includes('administrador') && !roles.includes('admin')) {
      router.push('/ordenes'); return;
    }
    setUser(u);
    loadData(token);
  }, []);

  const loadData = async (token: string) => {
    try {
      const headers = { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' };
      const [ordenesRes, clientesRes, repuestosRes] = await Promise.all([
        fetch(`${API}/api/v1/work-orders?per_page=10`, { headers }),
        fetch(`${API}/api/v1/customers?per_page=1`, { headers }),
        fetch(`${API}/api/v1/parts?per_page=1`, { headers }),
      ]);
      if (ordenesRes.ok) {
        const d = await ordenesRes.json();
        setOrdenes(d.data || []);
        setStats(s => ({ ...s, ordenes: d.meta?.total || d.data?.length || 0 }));
      }
      if (clientesRes.ok) {
        const d = await clientesRes.json();
        setStats(s => ({ ...s, clientes: d.meta?.total || 0 }));
      }
      if (repuestosRes.ok) {
        const d = await repuestosRes.json();
        setStats(s => ({ ...s, repuestos: d.meta?.total || 0 }));
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/');
  };

  const statusColor: Record<string, string> = {
    pendiente: 'bg-yellow-100 text-yellow-800',
    en_progreso: 'bg-blue-100 text-blue-800',
    completado: 'bg-green-100 text-green-800',
    cancelado: 'bg-red-100 text-red-800',
  };

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-red-600 text-white px-4 py-4 flex items-center justify-between shadow-lg">
        <div className="flex items-center gap-3">
          <img src="/icon.svg" alt="Logo" className="w-8 h-8 bg-white rounded-lg p-1" />
          <div>
            <h1 className="font-bold text-lg leading-tight">SerTecApp Admin</h1>
            <p className="text-red-100 text-xs">{user?.name}</p>
          </div>
        </div>
        <button onClick={logout} className="text-red-100 hover:text-white text-sm font-medium">
          Salir
        </button>
      </header>

      <main className="p-4 max-w-4xl mx-auto">
        {/* Stats */}
        <div className="grid grid-cols-2 gap-3 mb-6 mt-4">
          {[
            { label: 'Órdenes', value: stats.ordenes, icon: '📋', color: 'bg-blue-50 border-blue-200' },
            { label: 'Clientes', value: stats.clientes, icon: '👥', color: 'bg-green-50 border-green-200' },
            { label: 'Repuestos', value: stats.repuestos, icon: '🔩', color: 'bg-yellow-50 border-yellow-200' },
            { label: 'Sistema', value: '✓', icon: '🟢', color: 'bg-red-50 border-red-200' },
          ].map((s) => (
            <div key={s.label} className={`${s.color} border rounded-2xl p-4 flex items-center gap-3`}>
              <span className="text-2xl">{s.icon}</span>
              <div>
                <p className="text-2xl font-bold text-gray-800">{loading ? '...' : s.value}</p>
                <p className="text-xs text-gray-500">{s.label}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Accesos rápidos */}
        <div className="grid grid-cols-2 gap-3 mb-6">
          <a href="https://sertecapp.pendziuch.com/admin" target="_blank"
            className="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-all">
            <span className="text-2xl">🖥️</span>
            <div>
              <p className="font-semibold text-gray-800 text-sm">Panel Filament</p>
              <p className="text-xs text-gray-400">Admin completo</p>
            </div>
          </a>
          <button onClick={() => router.push('/ordenes')}
            className="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-all text-left w-full">
            <span className="text-2xl">👷</span>
            <div>
              <p className="font-semibold text-gray-800 text-sm">Vista Técnico</p>
              <p className="text-xs text-gray-400">Ver como técnico</p>
            </div>
          </button>
        </div>

        {/* Últimas órdenes */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 className="font-semibold text-gray-800">Últimas Órdenes</h2>
            <span className="text-xs text-gray-400">{stats.ordenes} total</span>
          </div>
          {loading ? (
            <div className="p-8 text-center text-gray-400">Cargando...</div>
          ) : ordenes.length === 0 ? (
            <div className="p-8 text-center text-gray-400">No hay órdenes</div>
          ) : (
            <div className="divide-y divide-gray-50">
              {ordenes.map((o: any) => (
                <div key={o.id} className="px-4 py-3 flex items-center justify-between hover:bg-gray-50 cursor-pointer"
                  onClick={() => router.push(`/detalle/${o.id}`)}>
                  <div>
                    <p className="font-medium text-gray-800 text-sm">#{o.id} — {o.customer?.name || 'Sin cliente'}</p>
                    <p className="text-xs text-gray-400">{o.equipment?.brand} {o.equipment?.model}</p>
                  </div>
                  <span className={`text-xs px-2 py-1 rounded-full font-medium ${statusColor[o.status] || 'bg-gray-100 text-gray-600'}`}>
                    {o.status}
                  </span>
                </div>
              ))}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
