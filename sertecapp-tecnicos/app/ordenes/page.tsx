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

const DEMO_ORDERS: Order[] = [
  {
    id: 1,
    clientName: 'Gym Centro',
    problem: 'Cinta no enciende',
    address: 'Av. Libertador 1234, CABA',
    priority: 'urgente',
    status: 'pendiente',
  },
  {
    id: 2,
    clientName: 'Club Fitness Sur',
    problem: 'Bici hace ruido en pedal derecho',
    address: 'Mitre 567, Avellaneda',
    priority: 'media',
    status: 'pendiente',
  },
  {
    id: 3,
    clientName: 'Fitness Company',
    problem: 'Remo pierde resistencia',
    address: 'San Martín 890, San Isidro',
    priority: 'alta',
    status: 'pendiente',
  },
  {
    id: 4,
    clientName: 'Ateneo Gym',
    problem: 'Revisión mensual programada',
    address: 'Belgrano 445, Vicente López',
    priority: 'baja',
    status: 'completado',
  },
  {
    id: 5,
    clientName: 'PowerGym',
    problem: 'Tablero LCD sin imagen',
    address: 'Rivadavia 2100, CABA',
    priority: 'alta',
    status: 'completado',
  },
];

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
      
      // Recargar órdenes
      const userData = localStorage.getItem('user');
      if (userData) {
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
          if (data.data && data.data.length > 0) {
            setOrders(data.data);
            cacheOrdenes(data.data);
          }
        }
      }
      
      alert(`Sincronizado: ${result.success} exitosos, ${result.failed} fallidos`);
    } catch (error) {
      console.error('Error en sync:', error);
      alert('Error al sincronizar');
    } finally {
      setSyncing(false);
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
    
    const loadOrders = async () => {
      try {
        const apiUrl = 'https://sertecapp.pendziuch.com';
        const response = await fetch(`${apiUrl}/api/v1/ordenes/tecnico/${user.id}`, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          },
          cache: 'no-store' // Forzar recarga sin cache
        });
        
        if (response.ok) {
          const data = await response.json();
          console.log('API Response:', data);
          setOrders(data.data || []);
          cacheOrdenes(data.data || []);
        } else {
          console.error('API Error:', response.status);
          setOrders([]);
        }
      } catch (error) {
        console.error('Error cargando órdenes:', error);
        setOrders([]);
      }
    };
    
    // Cargar órdenes al montar
    loadOrders();

    // Recargar cuando la ventana vuelve a tener foco
    const handleFocus = () => {
      console.log('Focus event - reloading orders');
      loadOrders();
      setPendingSync(getPartesPendientesSync().length);
    };
    window.addEventListener('focus', handleFocus);
    
    // Recargar cuando la página se hace visible (para mobile)
    const handleVisibilityChange = () => {
      if (!document.hidden) {
        console.log('Visibility change - reloading orders');
        loadOrders();
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
            loadOrders();
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
            <button
              onClick={handleLogout}
              className="text-sm text-gray-600 hover:text-gray-900 font-medium"
            >
              Salir
            </button>
          </div>

          {/* Filters + Status row */}
          <div className="flex items-center gap-2">
            <button
              onClick={() => setFilter('pending')}
              className={`flex-1 py-2 px-3 rounded-lg text-sm font-semibold transition-colors ${
                filter === 'pending'
                  ? 'bg-blue-600 text-white'
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
