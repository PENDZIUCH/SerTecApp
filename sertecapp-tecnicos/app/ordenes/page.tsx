'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { OrderCard } from '../components/OrderCard';
import { cacheOrdenes, getCachedOrdenes, isOnline, setupConnectionListener, syncPendingPartes, getPartesPendientesSync } from '../lib/storage';
import { useOnlineStatus } from '../../hooks/useOnlineStatus';
import { getGreeting } from '../../lib/utils';

interface Order {
  id: number;
  clientName: string;
  problem: string;
  address: string;
  priority: 'urgente' | 'alta' | 'media' | 'baja';
  status: 'pendiente' | 'en_progreso' | 'completado';
  suggestedParts?: Array<{ id: number; name: string; stock: number }>;
}

export default function OrdenesPage() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [orders, setOrders] = useState<Order[]>([]);
  const { isOnline: effectiveOnline, forceOffline, toggleForceOffline, realOnline } = useOnlineStatus();
  const [online, setOnline] = useState(true);
  const [pendingSync, setPendingSync] = useState(0);
  const [filter, setFilter] = useState<'pending' | 'completed'>('pending');
  const [syncing, setSyncing] = useState(false);
  const [menuOpen, setMenuOpen] = useState(false);

  const handleClearCache = () => {
    if (confirm('¿Limpiar caché y datos locales? Deberás volver a iniciar sesión.')) {
      localStorage.clear();
      router.push('/');
    }
  };

  const handleSync = async () => {
    setSyncing(true);
    try {
      const token = localStorage.getItem('token');
      if (!token) return;

      const apiUrl = 'https://sertecapp.pendziuch.com';
      const result = await syncPendingPartes(apiUrl, token);
      
      console.log('Sync result:', result);
      setPendingSync(getPartesPendientesSync().length);
      
      // Solo recargar pendientes después de sincronizar
      await loadPendingOrders();
      
      alert(`Sincronizado: ${result.success} exitosos, ${result.failed} fallidos`);
    } catch (error) {
      console.error('Error en sync:', error);
      alert('Error al sincronizar');
    } finally {
      setSyncing(false);
    }
  };

  const loadPendingOrders = async () => {
    try {
      const token = localStorage.getItem('token');
      const userData = localStorage.getItem('user');
      if (!token || !userData) return;

      const user = JSON.parse(userData);
      const apiUrl = 'https://sertecapp.pendziuch.com';
      const response = await fetch(`${apiUrl}/api/v1/ordenes/tecnico/${user.id}`, {
        headers: { 
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        },
        cache: 'no-store'
      });
      
      if (response.ok) {
        const data = await response.json();
        const newOrders = data.data || [];
        
        // Separar pendientes y completadas
        const pending = newOrders.filter((o: Order) => o.status === 'pendiente' || o.status === 'en_progreso');
        const completed = newOrders.filter((o: Order) => o.status === 'completado');
        
        // Obtener completadas del cache
        const cached = getCachedOrdenes() || [];
        const cachedCompleted = cached.filter((o: Order) => o.status === 'completado');
        
        // Merge: nuevas completadas + viejas completadas (sin duplicados)
        const completedIds = new Set(completed.map((o: Order) => o.id));
        const mergedCompleted = [
          ...completed,
          ...cachedCompleted.filter((o: Order) => !completedIds.has(o.id))
        ];
        
        // Combinar pendientes (frescas) + completadas (cache + nuevas)
        const finalOrders = [...pending, ...mergedCompleted];
        
        setOrders(finalOrders);
        cacheOrdenes(finalOrders);
        
        console.log(`Loaded: ${pending.length} pending, ${mergedCompleted.length} completed (${completed.length} new + ${cachedCompleted.length - completed.length} cached)`);
      } else {
        console.error('API Error:', response.status);
        // Cargar desde cache si falla
        const cached = getCachedOrdenes() || [];
        setOrders(cached);
      }
    } catch (error) {
      console.error('Error cargando órdenes:', error);
      // Cargar desde cache si falla
      const cached = getCachedOrdenes() || [];
      setOrders(cached);
    }
  };

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');

    if (!token || !userData) {
      router.push('/');
      return;
    }

    const user = JSON.parse(userData);
    console.log('USER DATA:', user);
    setUser(user);
    
    setOnline(isOnline());
    setPendingSync(getPartesPendientesSync().length);
    
    // Cargar desde cache primero (instantáneo)
    const cached = getCachedOrdenes() || [];
    if (cached.length > 0) {
      setOrders(cached);
    }
    
    // Luego actualizar pendientes desde servidor
    loadPendingOrders();

    // Recargar SOLO PENDIENTES cuando la ventana vuelve a tener foco
    const handleFocus = () => {
      console.log('Focus event - reloading pending orders only');
      loadPendingOrders();
      setPendingSync(getPartesPendientesSync().length);
    };
    window.addEventListener('focus', handleFocus);
    
    // Recargar cuando la página se hace visible (para mobile)
    const handleVisibilityChange = () => {
      if (!document.hidden) {
        console.log('Visibility change - reloading pending orders only');
        loadPendingOrders();
        setPendingSync(getPartesPendientesSync().length);
      }
    };
    document.addEventListener('visibilitychange', handleVisibilityChange);
    
    const cleanup = setupConnectionListener(
      async () => {
        setOnline(true);
        const token = localStorage.getItem('token');
        if (token) {
          const apiUrl = 'https://sertecapp.pendziuch.com';
          const result = await syncPendingPartes(apiUrl, token);
          if (result.success > 0) {
            setPendingSync(getPartesPendientesSync().length);
            await loadPendingOrders();
          }
        }
      },
      () => setOnline(false)
    );

    return () => {
      cleanup();
      window.removeEventListener('focus', handleFocus);
      document.removeEventListener('visibilitychange', handleVisibilityChange);
    };
  }, [router]);

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/');
  };

  const handleStart = (orderId: number) => {
    router.push(`/parte/${orderId}`);
  };

  const handleViewDetail = (orderId: number) => {
    router.push(`/detalle/${orderId}`);
  };

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-100 flex items-center justify-center">
        <div className="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  const pendingOrders = orders.filter((o) => o.status === 'pendiente' || o.status === 'en_progreso');
  const completedOrders = orders.filter((o) => o.status === 'completado');
  const filteredOrders = filter === 'pending' ? pendingOrders : completedOrders;

  return (
    <div className="min-h-screen bg-gray-900">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-3">
          {/* Top row: Logo + Logout */}
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-2">
              <img src="/fitness-logo.png" alt="Fitness Company" className="h-8 w-auto" />
            </div>
            <div className="flex items-center gap-3 relative">
              {/* User Menu Button with Status Indicator */}
              <button
                onClick={() => setMenuOpen(!menuOpen)}
                className="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg transition-colors"
              >
                {/* Status indicator with animated glow */}
                <div className="relative">
                  <div className={`w-2 h-2 rounded-full ${
                    effectiveOnline ? 'bg-green-500' : 'bg-red-500'
                  }`} />
                  {/* Animated pulse effect */}
                  <div className={`absolute inset-0 w-2 h-2 rounded-full animate-ping opacity-75 ${
                    effectiveOnline ? 'bg-green-400' : 'bg-red-400'
                  }`} />
                </div>
                <div className="text-right">
                  <p className="text-xs text-gray-500">{getGreeting()},</p>
                  <p className="text-sm font-semibold text-gray-900">{user.name || 'Técnico'}</p>
                </div>
                <svg className="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                </svg>
              </button>

              {/* Dropdown Menu */}
              {menuOpen && (
                <>
                  {/* Backdrop */}
                  <div 
                    className="fixed inset-0 z-10" 
                    onClick={() => setMenuOpen(false)}
                  />
                  
                  {/* Menu */}
                  <div className="absolute right-0 top-full mt-2 w-72 bg-white rounded-lg shadow-xl border border-gray-200 z-20">
                    {/* User Info */}
                    <div className="px-4 py-3 border-b border-gray-100">
                      <p className="text-sm font-semibold text-gray-900">{user.name || 'Técnico'}</p>
                      <p className="text-xs text-gray-500">{user.email}</p>
                    </div>

                    {/* Connection Status Toggle */}
                    <div className="px-4 py-3 border-b border-gray-100">
                      <div className="flex items-center justify-between mb-2">
                        <div className="flex items-center gap-2">
                          <div className={`w-2 h-2 rounded-full ${
                            realOnline ? 'bg-green-500' : 'bg-red-500'
                          }`} />
                          <span className="text-sm text-gray-700">
                            {forceOffline ? 'Modo Offline' : (realOnline ? 'Conectado' : 'Sin conexión')}
                          </span>
                        </div>
                        {/* Toggle Switch */}
                        <button
                          onClick={(e) => {
                            e.stopPropagation();
                            toggleForceOffline();
                          }}
                          className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                            forceOffline ? 'bg-gray-400' : 'bg-green-500'
                          }`}
                        >
                          <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                            forceOffline ? 'translate-x-1' : 'translate-x-6'
                          }`} />
                        </button>
                      </div>
                      {forceOffline && (
                        <p className="text-xs text-gray-500">
                          Modo offline manual para testing
                        </p>
                      )}
                    </div>

                    {/* Menu Items */}
                    <div className="py-2">
                      <button
                        onClick={() => {
                          handleSync();
                          setMenuOpen(false);
                        }}
                        disabled={syncing || !effectiveOnline}
                        className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center justify-between disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <div className="flex items-center gap-3">
                          <svg className="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                          </svg>
                          {syncing ? 'Sincronizando...' : 'Sincronizar'}
                        </div>
                        {pendingSync > 0 && (
                          <span className="bg-orange-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                            {pendingSync}
                          </span>
                        )}
                      </button>
                      
                      <button
                        onClick={() => {
                          window.location.reload();
                        }}
                        className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3"
                      >
                        <svg className="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refrescar App
                      </button>

                      <button
                        onClick={() => {
                          handleClearCache();
                          setMenuOpen(false);
                        }}
                        className="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-3"
                      >
                        <svg className="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Limpiar Caché
                      </button>
                      
                      <button
                        onClick={() => {
                          handleLogout();
                          setMenuOpen(false);
                        }}
                        className="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-3 border-t border-gray-100"
                      >
                        <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Cerrar Sesión
                      </button>
                    </div>
                  </div>
                </>
              )}
            </div>
          </div>

          {/* Filters + Status row */}
          <div className="flex items-center gap-2">
            <button
              onClick={() => setFilter('pending')}
              className={`flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition-colors ${
                filter === 'pending'
                  ? 'bg-red-600 text-white'
                  : 'bg-gray-100 text-gray-700'
              }`}
            >
              Pendientes ({pendingOrders.length})
            </button>
            <button
              onClick={() => setFilter('completed')}
              className={`flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition-colors ${
                filter === 'completed'
                  ? 'bg-green-600 text-white'
                  : 'bg-gray-100 text-gray-700'
              }`}
            >
              Completadas ({completedOrders.length})
            </button>
          </div>
        </div>
      </header>

      {/* Orders list */}
      <div className="max-w-7xl mx-auto px-4 py-4">
        {filteredOrders.length > 0 ? (
          <div className="space-y-3">
            {filteredOrders.map((order) => (
              <OrderCard
                key={order.id}
                {...order}
                onStart={() => handleStart(order.id)}
                onViewDetail={() => handleViewDetail(order.id)}
              />
            ))}
          </div>
        ) : (
          <div className="text-center py-12">
            <p className="text-gray-500">
              {filter === 'pending' ? 'No hay órdenes pendientes' : 'No hay órdenes completadas'}
            </p>
          </div>
        )}
      </div>
    </div>
  );
}
