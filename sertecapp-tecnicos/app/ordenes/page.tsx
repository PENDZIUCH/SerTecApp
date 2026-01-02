'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { OrderCard } from '../components/OrderCard';
import { cacheOrdenes, getCachedOrdenes, isOnline, setupConnectionListener, syncPendingPartes, getPartesPendientesSync } from '../lib/storage';

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
  const [online, setOnline] = useState(true);
  const [pendingSync, setPendingSync] = useState(0);
  const [filter, setFilter] = useState<'pending' | 'completed'>('pending');
  const [syncing, setSyncing] = useState(false);

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
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-3">
          {/* Top row: Logo + Logout */}
          <div className="flex items-center justify-between mb-3">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white text-sm font-bold">
                S
              </div>
              <h1 className="text-base font-semibold text-gray-900">SerTecApp</h1>
            </div>
            <div className="flex items-center gap-3">
              <div className="text-right">
                <p className="text-xs text-gray-500">Hola,</p>
                <p className="text-sm font-semibold text-gray-900">{user.name}</p>
              </div>
              <button
                onClick={handleLogout}
                className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                title="Cerrar sesión"
              >
                <svg className="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
              </button>
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
            
            {/* Status indicator */}
            <div className={`w-10 h-10 rounded-lg flex items-center justify-center relative ${
              online ? 'bg-green-50' : 'bg-red-50'
            }`}>
              <div className={`w-3 h-3 rounded-full ${
                online ? 'bg-green-500' : 'bg-red-500'
              }`} />
              {pendingSync > 0 && (
                <>
                  <span className="absolute -top-1 -right-1 w-5 h-5 bg-orange-500 text-white text-xs font-bold rounded-full flex items-center justify-center">
                    {pendingSync}
                  </span>
                  <button
                    onClick={handleSync}
                    disabled={syncing}
                    className="absolute -bottom-8 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs text-blue-600 font-medium"
                  >
                    {syncing ? 'Sincronizando...' : 'Sincronizar'}
                  </button>
                </>
              )}
            </div>
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
