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

// DATOS DEMO - Cuando tengamos backend, esto viene del API
const DEMO_ORDERS: Order[] = [
  {
    id: 1,
    clientName: 'Gym Centro',
    problem: 'Cinta no enciende',
    address: 'Av. Libertador 1234, CABA',
    priority: 'urgente',
    status: 'pendiente',
    suggestedParts: [
      { id: 1, name: 'Banda PT300', stock: 3 },
      { id: 2, name: 'Lubricante Silicona', stock: 5 },
    ],
  },
  {
    id: 2,
    clientName: 'Club Fitness Sur',
    problem: 'Bici hace ruido en pedal derecho',
    address: 'Mitre 567, Avellaneda',
    priority: 'media',
    status: 'pendiente',
    suggestedParts: [
      { id: 3, name: 'Rodamiento pedal', stock: 8 },
    ],
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

  useEffect(() => {
    // Verificar autenticación
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');

    if (!token || !userData) {
      router.push('/');
      return;
    }

    const user = JSON.parse(userData);
    setUser(user);
    
    // Detectar estado de conexión
    setOnline(isOnline());
    setPendingSync(getPartesPendientesSync().length);
    
    // Cargar órdenes desde backend o cache
    const loadOrders = async () => {
      try {
        const response = await fetch(`${process.env.NEXT_PUBLIC_API_URL}/api/v1/ordenes/tecnico/${user.id}`, {
          headers: { 
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        });
        
        if (response.ok) {
          const data = await response.json();
          // Si el backend devuelve array vacío, usar datos demo
          if (data.data && data.data.length > 0) {
            setOrders(data.data);
            cacheOrdenes(data.data);
          } else {
            // Backend vacío = mostrar datos demo
            setOrders(DEMO_ORDERS);
            cacheOrdenes(DEMO_ORDERS);
          }
        } else {
          // Si falla, usar cache
          const cached = getCachedOrdenes();
          if (cached) {
            setOrders(cached);
          } else {
            // Fallback a datos demo si no hay cache
            setOrders(DEMO_ORDERS);
          }
        }
      } catch (error) {
        console.error('Error cargando órdenes:', error);
        // Usar cache si hay error de red
        const cached = getCachedOrdenes();
        if (cached) {
          setOrders(cached);
        } else {
          setOrders(DEMO_ORDERS);
        }
      }
    };
    
    loadOrders();
    
    // Setup listeners
    const cleanup = setupConnectionListener(
      async () => {
        setOnline(true);
        // Auto-sync cuando vuelve conexión
        const token = localStorage.getItem('token');
        if (token) {
          const result = await syncPendingPartes(
            process.env.NEXT_PUBLIC_API_URL || '',
            token
          );
          if (result.success > 0) {
            setPendingSync(getPartesPendientesSync().length);
            // Recargar órdenes después de sincronizar
            loadOrders();
          }
        }
      },
      () => setOnline(false)
    );

    return cleanup;
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

  const pendingOrders = orders.filter((o) => o.status === 'pendiente');
  const completedOrders = orders.filter((o) => o.status === 'completado');

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
        <div className="max-w-7xl mx-auto px-4 py-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white text-xl font-bold">
                S
              </div>
              <div>
                <h1 className="text-base font-semibold text-gray-900">Mis Órdenes</h1>
                <p className="text-sm text-gray-500">{user.nombre}</p>
              </div>
            </div>
            <button
              onClick={handleLogout}
              className="text-sm text-gray-600 hover:text-gray-900 font-medium"
            >
              Salir
            </button>
          </div>
          
          {/* Connection Status */}
          <div className="mt-3 flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div className={`w-2 h-2 rounded-full ${
                online ? 'bg-green-500' : 'bg-red-500'
              }`} />
              <span className="text-xs text-gray-600">
                {online ? 'En línea' : 'Sin conexión'}
              </span>
            </div>
            
            {pendingSync > 0 && (
              <div className="flex items-center gap-1.5 bg-orange-50 text-orange-700 px-2 py-1 rounded-full">
                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span className="text-xs font-medium">{pendingSync} pendiente{pendingSync > 1 ? 's' : ''}</span>
              </div>
            )}
          </div>
        </div>
      </header>

      {/* Stats + Filtros */}
      <div className="max-w-7xl mx-auto px-4 py-6">
        {/* Botones de filtro */}
        <div className="flex gap-2 mb-6">
          <button
            onClick={() => setFilter('pending')}
            className={`flex-1 py-3 rounded-lg font-semibold transition-all ${
              filter === 'pending'
                ? 'bg-blue-600 text-white shadow-md'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <div className="flex items-center justify-center gap-2">
              <span>Pendientes</span>
              <span className={`text-xs px-2 py-0.5 rounded-full ${
                filter === 'pending' ? 'bg-white/20' : 'bg-gray-200'
              }`}>
                {pendingOrders.length}
              </span>
            </div>
          </button>
          <button
            onClick={() => setFilter('completed')}
            className={`flex-1 py-3 rounded-lg font-semibold transition-all ${
              filter === 'completed'
                ? 'bg-green-600 text-white shadow-md'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <div className="flex items-center justify-center gap-2">
              <span>Completadas</span>
              <span className={`text-xs px-2 py-0.5 rounded-full ${
                filter === 'completed' ? 'bg-white/20' : 'bg-gray-200'
              }`}>
                {completedOrders.length}
              </span>
            </div>
          </button>
        </div>

        {/* Órdenes filtradas */}
        <div className="space-y-3">
          {(filter === 'pending' ? pendingOrders : completedOrders).map((order) => (
            <OrderCard
              key={order.id}
              {...order}
              onStart={() => handleStart(order.id)}
              onViewDetail={() => handleViewDetail(order.id)}
            />
          ))}

          {(filter === 'pending' ? pendingOrders : completedOrders).length === 0 && (
            <div className="text-center py-12">
              <p className="text-gray-500">
                {filter === 'pending' ? 'No hay órdenes pendientes' : 'No hay órdenes completadas'}
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
