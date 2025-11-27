'use client';

import { useState, useEffect } from 'react';
import AdminLayout from './layouts/AdminLayout';
import ClienteForm from './components/ClienteForm';
import OrdenForm from './components/OrdenForm';
import OrdenDetalle from './components/OrdenDetalle';
import Toast from './components/Toast';
import { useDarkMode } from './hooks/useDarkMode';

const API_BASE = process.env.NEXT_PUBLIC_API_URL || 'https://demo.pendziuch.com/backend/api';

export default function Home() {
  const { isDark, toggle } = useDarkMode();
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [token, setToken] = useState('');
  const [user, setUser] = useState<any>(null);
  const [view, setView] = useState('dashboard');
  const [clientes, setClientes] = useState<any[]>([]);
  const [ordenes, setOrdenes] = useState<any[]>([]);
  const [stats, setStats] = useState<{totalClientes?: number, totalOrdenes?: number, ordenesHoy?: number}>({});
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [showClienteForm, setShowClienteForm] = useState(false);
  const [editingCliente, setEditingCliente] = useState(null);
  const [showOrdenForm, setShowOrdenForm] = useState(false);
  const [editingOrden, setEditingOrden] = useState(null);
  const [showOrdenDetalle, setShowOrdenDetalle] = useState(false);
  const [selectedOrdenId, setSelectedOrdenId] = useState<number | null>(null);
  const [toast, setToast] = useState<{message: string, type: 'success' | 'error' | 'info'} | null>(null);

  // Cargar sesión del localStorage al iniciar
  useEffect(() => {
    const savedToken = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    
    if (savedToken && savedUser) {
      setToken(savedToken);
      setUser(JSON.parse(savedUser));
      setIsLoggedIn(true);
    }
  }, []);

  // Cargar datos cuando se inicia sesión
  useEffect(() => {
    if (isLoggedIn) {
      loadData();
    }
  }, [isLoggedIn]);

  // Login
  const handleLogin = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    
    const email = (e.target as any).email.value;
    const password = (e.target as any).password.value;
    
    try {
      const res = await fetch(`${API_BASE}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await res.json();
      
      console.log('Login response:', data);
      
      if (data.success) {
        const userData = data.data.user;
        const userToken = data.data.token;
        
        // Guardar en localStorage
        localStorage.setItem('token', userToken);
        localStorage.setItem('user', JSON.stringify(userData));
        
        setToken(userToken);
        setUser(userData);
        setIsLoggedIn(true);
        setToast({ message: `¡Bienvenido ${userData.nombre}!`, type: 'success' });
      } else {
        setError(data.message || 'Error al iniciar sesión');
      }
    } catch (err) {
      console.error('Login error:', err);
      setError('Error de conexión. Verifica que el servidor esté corriendo.');
    } finally {
      setLoading(false);
    }
  };

  // Logout
  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    setToken('');
    setUser(null);
    setIsLoggedIn(false);
    setToast({ message: 'Sesión cerrada', type: 'info' });
  };

  // Load data
  const loadData = async () => {
    const [clientesRes, ordenesRes] = await Promise.all([
      fetch(`${API_BASE}/clientes`),
      fetch(`${API_BASE}/ordenes`)
    ]);
    
    const clientesData = await clientesRes.json();
    const ordenesData = await ordenesRes.json();
    
    setClientes(clientesData.data?.data || []);
    setOrdenes(ordenesData.data?.data || []);
    
    setStats({
      totalClientes: clientesData.data?.total || 0,
      totalOrdenes: ordenesData.data?.total || 0,
      ordenesHoy: ordenesData.data?.data.filter((o: any) => 
        o.fecha_trabajo === new Date().toISOString().split('T')[0]
      ).length || 0
    });
  };

  useEffect(() => {
    if (isLoggedIn) loadData();
  }, [isLoggedIn]);

  if (!isLoggedIn) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-600 to-indigo-700 flex items-center justify-center p-4">
        <div className="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md">
          <div className="text-center mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-2">🔧 SerTecApp</h1>
            <p className="text-gray-600">Sistema de Gestión Técnica</p>
          </div>
          
          <form onSubmit={handleLogin} className="space-y-6">
            {error && (
              <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {error}
              </div>
            )}
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Email</label>
              <input
                type="email"
                name="email"
                defaultValue="admin@sertecapp.com"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              />
            </div>
            
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">Password</label>
              <input
                type="password"
                name="password"
                defaultValue="admin123"
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 bg-white"
                required
              />
            </div>
            
            <button
              type="submit"
              disabled={loading}
              className="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
            >
              {loading ? 'Ingresando...' : 'Ingresar'}
            </button>
          </form>
        </div>
      </div>
    );
  }

  return (
    <AdminLayout
      currentView={view}
      onViewChange={setView}
      user={user}
      onLogout={handleLogout}
      isDark={isDark}
      onToggleDark={toggle}
    >
      {/* CONTENIDO: Dashboard, Clientes, Órdenes - TODO IGUAL QUE ANTES */}
      
      {/* VISTA DASHBOARD */}
      {view === 'dashboard' && (
          <div>
            <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-4 sm:mb-6">Dashboard</h2>
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-6 mb-6 sm:mb-8">
              <button 
                onClick={() => setView('clientes')}
                className="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow hover:shadow-lg transition cursor-pointer text-left"
              >
                <div className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-1">Total Clientes</div>
                <div className="text-2xl sm:text-3xl font-bold text-blue-600 dark:text-blue-400">{stats.totalClientes}</div>
                <div className="text-xs text-gray-500 dark:text-gray-500 mt-2">👉 Ver todos</div>
              </button>
              <button 
                onClick={() => setView('ordenes')}
                className="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow hover:shadow-lg transition cursor-pointer text-left"
              >
                <div className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-1">Total Órdenes</div>
                <div className="text-2xl sm:text-3xl font-bold text-green-600 dark:text-green-400">{stats.totalOrdenes}</div>
                <div className="text-xs text-gray-500 dark:text-gray-500 mt-2">👉 Ver todas</div>
              </button>
              <button 
                onClick={() => setView('ordenes')}
                className="bg-white dark:bg-gray-800 p-4 sm:p-6 rounded-lg shadow hover:shadow-lg transition cursor-pointer text-left sm:col-span-2 lg:col-span-1"
              >
                <div className="text-xs sm:text-sm text-gray-600 dark:text-gray-400 mb-1">Órdenes Hoy</div>
                <div className="text-2xl sm:text-3xl font-bold text-orange-600 dark:text-orange-400">{stats.ordenesHoy}</div>
                <div className="text-xs text-gray-500 dark:text-gray-500 mt-2">👉 Ver todas</div>
              </button>
            </div>
          </div>
        )}

        {view === 'clientes' && (
          <div>
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4 sm:mb-6">
              <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">Clientes</h2>
              <button 
                onClick={() => setShowClienteForm(true)}
                className="w-full sm:w-auto bg-green-600 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg hover:bg-green-700 transition font-medium"
              >
                + Nuevo Cliente
              </button>
            </div>

            {/* Vista Mobile - Cards */}
            <div className="lg:hidden space-y-3">
              {clientes.map(c => (
                <div key={c.id} className="bg-white rounded-lg shadow p-4">
                  <div className="flex justify-between items-start mb-3">
                    <div className="flex-1">
                      <h3 className="font-bold text-gray-900">{c.nombre}</h3>
                      <p className="text-sm text-gray-600">{c.telefono}</p>
                    </div>
                    <div className="flex flex-col items-end gap-2">
                      <span className={`px-2 py-1 rounded text-xs font-medium ${c.tipo === 'abonado' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}`}>
                        {c.tipo}
                      </span>
                      <span className={`px-2 py-1 rounded text-xs font-medium ${c.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                        {c.estado}
                      </span>
                    </div>
                  </div>
                  <div className="flex justify-between items-center pt-3 border-t">
                    <span className="text-sm text-gray-600">
                      {c.frecuencia_visitas} visitas/mes
                    </span>
                    <button
                      onClick={() => {
                        setEditingCliente(c);
                        setShowClienteForm(true);
                      }}
                      className="px-4 py-2 text-sm bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition font-medium"
                    >
                      Editar
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Vista Desktop - Tabla */}
            <div className="hidden lg:block bg-white rounded-lg shadow overflow-hidden">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Visitas/Mes</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {clientes.map(c => (
                    <tr key={c.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm font-medium text-gray-900">{c.nombre}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">
                        <span className={`px-2 py-1 rounded text-xs ${c.tipo === 'abonado' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'}`}>
                          {c.tipo}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{c.frecuencia_visitas}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">{c.telefono}</td>
                      <td className="px-6 py-4 text-sm">
                        <span className={`px-2 py-1 rounded text-xs ${c.estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                          {c.estado}
                        </span>
                      </td>
                      <td className="px-6 py-4 text-sm">
                        <button
                          onClick={() => {
                            setEditingCliente(c);
                            setShowClienteForm(true);
                          }}
                          className="text-blue-600 hover:text-blue-800 font-medium"
                        >
                          Editar
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        )}

        {view === 'ordenes' && (
          <div>
            <div className="flex justify-between items-center mb-6">
              <h2 className="text-3xl font-bold text-gray-900">Órdenes de Trabajo</h2>
              <button 
                onClick={() => setShowOrdenForm(true)}
                className="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition"
              >
                + Nueva Orden
              </button>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {ordenes.map(o => (
                <div key={o.id} className="bg-white rounded-lg shadow p-6 hover:shadow-lg transition cursor-pointer">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <div className="font-bold text-lg text-gray-900">#{o.numero_parte}</div>
                      <div className="text-sm text-gray-600">{o.cliente_nombre}</div>
                    </div>
                    <span className={`px-3 py-1 rounded text-xs font-medium ${
                      o.estado === 'completado' ? 'bg-green-100 text-green-800' :
                      o.estado === 'en_progreso' ? 'bg-blue-100 text-blue-800' :
                      'bg-yellow-100 text-yellow-800'
                    }`}>
                      {o.estado}
                    </span>
                  </div>
                  <p className="text-sm text-gray-700 mb-4 line-clamp-2">{o.descripcion_trabajo}</p>
                  <div className="flex justify-between items-center text-sm">
                    <span className="text-gray-600">📅 {o.fecha_trabajo}</span>
                    <span className="font-bold text-gray-900">${Number(o.total).toLocaleString()}</span>
                  </div>
                  <div className="mt-4 pt-4 border-t flex gap-2">
                    <button
                      onClick={() => {
                        setEditingOrden(o);
                        setShowOrdenForm(true);
                      }}
                      className="flex-1 px-3 py-2 text-sm bg-blue-50 text-blue-600 rounded hover:bg-blue-100 transition"
                    >
                      Editar
                    </button>
                    <button
                      onClick={(e) => {
                        e.stopPropagation();
                        setSelectedOrdenId(o.id);
                        setShowOrdenDetalle(true);
                      }}
                      className="flex-1 px-3 py-2 text-sm bg-gray-50 text-gray-600 rounded hover:bg-gray-100 transition"
                    >
                      Ver Detalle
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

      {/* Modal de Cliente */}
      {showClienteForm && (
        <ClienteForm
          onClose={() => {
            setShowClienteForm(false);
            setEditingCliente(null);
          }}
          onSuccess={(message) => {
            loadData();
            setToast({ message, type: 'success' });
          }}
          cliente={editingCliente}
          apiBase={API_BASE}
        />
      )}

      {/* Modal de Orden */}
      {showOrdenForm && (
        <OrdenForm
          onClose={() => {
            setShowOrdenForm(false);
            setEditingOrden(null);
          }}
          onSuccess={(message) => {
            loadData();
            setToast({ message, type: 'success' });
          }}
          orden={editingOrden}
          apiBase={API_BASE}
        />
      )}

      {/* Modal Detalle de Orden */}
      {showOrdenDetalle && selectedOrdenId && (
        <OrdenDetalle
          ordenId={selectedOrdenId}
          onClose={() => {
            setShowOrdenDetalle(false);
            setSelectedOrdenId(null);
          }}
          apiBase={API_BASE}
        />
      )}

      {/* Toast Notification */}
      {toast && (
        <Toast
          message={toast.message}
          type={toast.type}
          onClose={() => setToast(null)}
        />
      )}
    </AdminLayout>
  );
}
