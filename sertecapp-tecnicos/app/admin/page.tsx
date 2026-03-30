'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';

const API = 'https://sertecapp.pendziuch.com';

interface Order { id: number; title: string; status: string; customer?: any; equipment?: any; }
interface Customer { id: number; name: string; }
interface Tech { id: number; name: string; }
interface Equipment { id: number; brand: string; model: string; customer_id: number; }

const statusColor: Record<string, string> = {
  pendiente: 'bg-yellow-100 text-yellow-800',
  en_progreso: 'bg-blue-100 text-blue-800',
  completado: 'bg-green-100 text-green-800',
  cancelado: 'bg-red-100 text-red-800',
};

const prioridades = [
  { value: 'low', label: 'Baja' },
  { value: 'medium', label: 'Media' },
  { value: 'high', label: 'Alta' },
  { value: 'urgent', label: 'Urgente' },
];

export default function AdminPage() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [token, setToken] = useState('');
  const [stats, setStats] = useState({ ordenes: 0, clientes: 0, repuestos: 0 });
  const [ordenes, setOrdenes] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);

  // Datos para el formulario
  const [clientes, setClientes] = useState<Customer[]>([]);
  const [tecnicos, setTecnicos] = useState<Tech[]>([]);
  const [equipos, setEquipos] = useState<Equipment[]>([]);
  const [equiposFiltrados, setEquiposFiltrados] = useState<Equipment[]>([]);

  // Form
  const [form, setForm] = useState({
    customer_id: '',
    equipment_id: '',
    title: '',
    description: '',
    priority: 'medium',
    assigned_tech_id: '',
    scheduled_date: '',
    requires_signature: false,
  });
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState('');

  useEffect(() => {
    const t = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!t || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    const roles: string[] = u?.roles || [];
    if (!roles.includes('administrador') && !roles.includes('admin')) {
      router.push('/ordenes'); return;
    }
    setUser(u);
    setToken(t);
    loadData(t);
  }, []);

  const headers = (t: string) => ({ 'Authorization': `Bearer ${t}`, 'Accept': 'application/json', 'Content-Type': 'application/json' });

  const loadData = async (t: string) => {
    try {
      const h = { 'Authorization': `Bearer ${t}`, 'Accept': 'application/json' };
      const [ordenesRes, clientesRes, repuestosRes, tecnicosRes, equiposRes] = await Promise.all([
        fetch(`${API}/api/v1/work-orders?per_page=15`, { headers: h }),
        fetch(`${API}/api/v1/customers?per_page=500`, { headers: h }),
        fetch(`${API}/api/v1/parts?per_page=1`, { headers: h }),
        fetch(`${API}/api/v1/users?per_page=100`, { headers: h }),
        fetch(`${API}/api/v1/equipments?per_page=500`, { headers: h }),
      ]);
      if (ordenesRes.ok) {
        const d = await ordenesRes.json();
        setOrdenes(d.data || []);
        setStats(s => ({ ...s, ordenes: d.meta?.total || 0 }));
      }
      if (clientesRes.ok) {
        const d = await clientesRes.json();
        setClientes(d.data || []);
        setStats(s => ({ ...s, clientes: d.meta?.total || 0 }));
      }
      if (repuestosRes.ok) {
        const d = await repuestosRes.json();
        setStats(s => ({ ...s, repuestos: d.meta?.total || 0 }));
      }
      if (tecnicosRes.ok) {
        const d = await tecnicosRes.json();
        setTecnicos(d.data || []);
      }
      if (equiposRes.ok) {
        const d = await equiposRes.json();
        setEquipos(d.data || []);
      }
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  // Filtrar equipos por cliente seleccionado
  useEffect(() => {
    if (form.customer_id) {
      const filtrados = equipos.filter(e => String(e.customer_id) === String(form.customer_id));
      setEquiposFiltrados(filtrados);
      setForm(f => ({ ...f, equipment_id: '' }));
    } else {
      setEquiposFiltrados([]);
    }
  }, [form.customer_id, equipos]);

  const abrirModal = () => {
    setForm({ customer_id: '', equipment_id: '', title: '', description: '', priority: 'medium', assigned_tech_id: '', scheduled_date: '', requires_signature: false });
    setFormError('');
    setShowModal(true);
  };

  const crearOrden = async () => {
    if (!form.customer_id || !form.title.trim()) {
      setFormError('Cliente y título son obligatorios');
      return;
    }
    setSaving(true);
    setFormError('');
    try {
      const body: any = {
        customer_id: parseInt(form.customer_id),
        title: form.title,
        description: form.description,
        priority: form.priority,
        requires_signature: form.requires_signature,
      };
      if (form.equipment_id) body.equipment_id = parseInt(form.equipment_id);
      if (form.assigned_tech_id) body.assigned_tech_id = parseInt(form.assigned_tech_id);
      if (form.scheduled_date) body.scheduled_date = form.scheduled_date;

      const res = await fetch(`${API}/api/v1/work-orders`, {
        method: 'POST',
        headers: headers(token),
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (res.ok) {
        setShowModal(false);
        loadData(token);
      } else {
        const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : data.message;
        setFormError(String(msgs) || 'Error al crear la orden');
      }
    } catch (e) {
      setFormError('Error de conexión');
    } finally {
      setSaving(false);
    }
  };

  const logout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    router.push('/');
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
        <button onClick={logout} className="text-red-100 hover:text-white text-sm font-medium">Salir</button>
      </header>

      <main className="p-4 max-w-4xl mx-auto">
        {/* Stats */}
        <div className="grid grid-cols-2 gap-3 mb-4 mt-4">
          {[
            { label: 'Órdenes', value: stats.ordenes, icon: '📋' },
            { label: 'Clientes', value: stats.clientes, icon: '👥' },
            { label: 'Repuestos', value: stats.repuestos, icon: '🔩' },
            { label: 'Sistema', value: '✓', icon: '🟢' },
          ].map((s) => (
            <div key={s.label} className="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
              <span className="text-2xl">{s.icon}</span>
              <div>
                <p className="text-2xl font-bold text-gray-800">{loading ? '...' : s.value}</p>
                <p className="text-xs text-gray-500">{s.label}</p>
              </div>
            </div>
          ))}
        </div>

        {/* Accesos rápidos */}
        <div className="grid grid-cols-2 gap-3 mb-4">
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

        {/* Órdenes */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 className="font-semibold text-gray-800">Órdenes de Trabajo</h2>
            <button onClick={abrirModal}
              className="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
              + Nueva Orden
            </button>
          </div>
          {loading ? (
            <div className="p-8 text-center text-gray-400">Cargando...</div>
          ) : ordenes.length === 0 ? (
            <div className="p-8 text-center text-gray-400">No hay órdenes</div>
          ) : (
            <div className="divide-y divide-gray-50">
              {ordenes.map((o) => (
                <div key={o.id} className="px-4 py-3 flex items-center justify-between hover:bg-gray-50 cursor-pointer"
                  onClick={() => router.push(`/detalle/${o.id}`)}>
                  <div>
                    <p className="font-medium text-gray-800 text-sm">#{o.id} — {o.customer?.name || 'Sin cliente'}</p>
                    <p className="text-xs text-gray-500">{o.title}</p>
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

      {/* Modal Nueva Orden */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div className="bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl">
              <h2 className="font-bold text-gray-800 text-lg">Nueva Orden</h2>
              <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
            </div>
            <div className="p-6 space-y-4">
              {/* Cliente */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                <select value={form.customer_id} onChange={e => setForm(f => ({ ...f, customer_id: e.target.value }))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                  <option value="">Seleccionar cliente...</option>
                  {clientes.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
                </select>
              </div>
              {/* Equipo */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Equipo</label>
                <select value={form.equipment_id} onChange={e => setForm(f => ({ ...f, equipment_id: e.target.value }))}
                  disabled={!form.customer_id}
                  className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-transparent disabled:bg-gray-50 disabled:text-gray-400">
                  <option value="">{form.customer_id ? (equiposFiltrados.length === 0 ? 'Sin equipos registrados' : 'Seleccionar equipo...') : 'Primero seleccioná un cliente'}</option>
                  {equiposFiltrados.map(e => <option key={e.id} value={e.id}>{e.brand} {e.model}</option>)}
                </select>
              </div>
              {/* Título */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))}
                  placeholder="Ej: Reparación cinta caminadora"
                  className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-transparent" />
              </div>
              {/* Descripción */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
                  rows={3} placeholder="Detalles del problema..."
                  className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none" />
              </div>
              {/* Prioridad + Técnico */}
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                  <select value={form.priority} onChange={e => setForm(f => ({ ...f, priority: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    {prioridades.map(p => <option key={p.value} value={p.value}>{p.label}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Técnico</label>
                  <select value={form.assigned_tech_id} onChange={e => setForm(f => ({ ...f, assigned_tech_id: e.target.value }))}
                    className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">Sin asignar</option>
                    {tecnicos.map(t => <option key={t.id} value={t.id}>{t.name}</option>)}
                  </select>
                </div>
              </div>
              {/* Fecha */}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Fecha programada</label>
                <input type="date" value={form.scheduled_date} onChange={e => setForm(f => ({ ...f, scheduled_date: e.target.value }))}
                  className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-red-500 focus:border-transparent" />
              </div>
              {/* Requiere firma */}
              <div className="flex items-center gap-3">
                <input type="checkbox" id="firma" checked={form.requires_signature}
                  onChange={e => setForm(f => ({ ...f, requires_signature: e.target.checked }))}
                  className="w-4 h-4 text-red-600 rounded" />
                <label htmlFor="firma" className="text-sm text-gray-700">Requiere firma del cliente</label>
              </div>
              {formError && <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{formError}</div>}
              <button onClick={crearOrden} disabled={saving}
                className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl transition-all disabled:opacity-50">
                {saving ? 'Creando...' : 'Crear Orden'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
