'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { API_URL } from '../../lib/config';

const API = API_URL;

interface Order { id: number; title: string; status: string; priority?: string; customer?: any; equipment?: any; assigned_tech?: any; }
interface Customer { id: number; full_name: string; business_name: string; }
interface Tech { id: number; name: string; }
interface Equipment { id: number; brand: string; model: string; customer_id: number; }

const statusColor: Record<string, string> = {
  pendiente: 'bg-yellow-100 text-yellow-800', en_progreso: 'bg-blue-100 text-blue-800',
  completado: 'bg-green-100 text-green-800', completed: 'bg-green-100 text-green-800',
  cancelado: 'bg-red-100 text-red-800',
};
const prioColor: Record<string, string> = {
  low: 'border-l-gray-400', medium: 'border-l-yellow-400',
  high: 'border-l-orange-500', urgent: 'border-l-red-600',
};
const prioridades = [
  { value: 'low', label: 'Baja' }, { value: 'medium', label: 'Media' },
  { value: 'high', label: 'Alta' }, { value: 'urgent', label: 'Urgente' },
];
const selectClass = "w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-red-500 bg-white";
const normalizeStatus = (s: string) => ({ completed:'completado', in_progress:'en_progreso', pending:'pendiente' }[s] ?? s);

export default function AdminPage() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [token, setToken] = useState('');
  const [stats, setStats] = useState({ ordenes: 0, clientes: 0, repuestos: 0 });
  const [ordenes, setOrdenes] = useState<Order[]>([]);
  const [ordenesTecnico, setOrdenesTecnico] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadingTecnico, setLoadingTecnico] = useState(false);
  const [vista, setVista] = useState<'admin' | 'tecnico'>('admin');
  const [showModal, setShowModal] = useState(false);
  const [clientes, setClientes] = useState<Customer[]>([]);
  const [tecnicos, setTecnicos] = useState<Tech[]>([]);
  const [equipos, setEquipos] = useState<Equipment[]>([]);
  const [equiposFiltrados, setEquiposFiltrados] = useState<Equipment[]>([]);
  const [form, setForm] = useState({
    customer_id: '', equipment_id: '', title: '', description: '',
    priority: 'medium', assigned_tech_id: '', scheduled_date: '', requires_signature: false,
  });
  const [saving, setSaving] = useState(false);
  const [formError, setFormError] = useState('');

  useEffect(() => {
    const t = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!t || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    const roles: string[] = u?.roles || [];
    if (!roles.includes('administrador') && !roles.includes('admin')) { router.push('/ordenes'); return; }
    setUser(u); setToken(t); loadData(t);
  }, []);

  const hd = (t: string) => ({ 'Authorization': `Bearer ${t}`, 'Accept': 'application/json', 'Content-Type': 'application/json' });

  const loadData = async (t: string) => {
    try {
      const h = { 'Authorization': `Bearer ${t}`, 'Accept': 'application/json' };
      const [ordenesRes, clientesRes, repuestosRes, tecnicosRes, equiposRes] = await Promise.all([
        fetch(`${API}/api/v1/work-orders?per_page=50`, { headers: h }),
        fetch(`${API}/api/v1/customers?per_page=500`, { headers: h }),
        fetch(`${API}/api/v1/parts?per_page=1`, { headers: h }),
        fetch(`${API}/api/v1/users?per_page=100`, { headers: h }),
        fetch(`${API}/api/v1/equipments?per_page=500`, { headers: h }),
      ]);
      if (ordenesRes.ok) { const d = await ordenesRes.json(); setOrdenes(d.data || []); setStats(s => ({ ...s, ordenes: d.meta?.total || 0 })); }
      if (clientesRes.ok) { const d = await clientesRes.json(); setClientes(d.data || []); setStats(s => ({ ...s, clientes: d.meta?.total || 0 })); }
      if (repuestosRes.ok) { const d = await repuestosRes.json(); setStats(s => ({ ...s, repuestos: d.meta?.total || 0 })); }
      if (tecnicosRes.ok) { const d = await tecnicosRes.json(); setTecnicos(d.data || []); }
      if (equiposRes.ok) { const d = await equiposRes.json(); setEquipos(d.data || []); }
    } catch (e) { console.error(e); } finally { setLoading(false); }
  };

  const verVistasTecnico = async (userId: number) => {
    setVista('tecnico'); setLoadingTecnico(true);
    try {
      const res = await fetch(`${API}/api/v1/ordenes/tecnico/${userId}`, {
        headers: { 'Authorization': `Bearer ${token}`, 'Accept': 'application/json' }
      });
      if (res.ok) { const d = await res.json(); setOrdenesTecnico(d.data || []); }
    } catch (e) { console.error(e); } finally { setLoadingTecnico(false); }
  };

  useEffect(() => {
    if (form.customer_id) {
      setEquiposFiltrados(equipos.filter(e => String(e.customer_id) === String(form.customer_id)));
      setForm(f => ({ ...f, equipment_id: '' }));
    } else { setEquiposFiltrados([]); }
  }, [form.customer_id, equipos]);

  const abrirModal = () => {
    setForm({ customer_id: '', equipment_id: '', title: '', description: '', priority: 'medium', assigned_tech_id: '', scheduled_date: '', requires_signature: false });
    setFormError(''); setShowModal(true);
  };

  const nombreCliente = (c: Customer) => c.business_name || c.full_name || '';

  const crearOrden = async () => {
    if (!form.customer_id || !form.title.trim()) { setFormError('Cliente y título son obligatorios'); return; }
    setSaving(true); setFormError('');
    try {
      const body: any = { customer_id: parseInt(form.customer_id), title: form.title, description: form.description, priority: form.priority, requires_signature: form.requires_signature };
      if (form.equipment_id) body.equipment_id = parseInt(form.equipment_id);
      if (form.assigned_tech_id) body.assigned_tech_id = parseInt(form.assigned_tech_id);
      if (form.scheduled_date) body.scheduled_date = form.scheduled_date;
      const res = await fetch(`${API}/api/v1/work-orders`, { method: 'POST', headers: hd(token), body: JSON.stringify(body) });
      const data = await res.json();
      if (res.ok) { setShowModal(false); loadData(token); }
      else { setFormError(data.errors ? Object.values(data.errors).flat().join(' ') : data.message || 'Error al crear'); }
    } catch (e) { setFormError('Error de conexión'); } finally { setSaving(false); }
  };

  const logout = () => { localStorage.clear(); router.push('/'); };

  if (vista === 'tecnico') {
    const pendientes = ordenesTecnico.filter(o => o.status === 'pendiente' || o.status === 'en_progreso');
    const completadas = ordenesTecnico.filter(o => o.status === 'completado');
    return (
      <div className="min-h-screen bg-gray-900">
        <header className="bg-white border-b border-gray-200 sticky top-0 z-10">
          <div className="max-w-4xl mx-auto px-4 py-3 flex items-center gap-3">
            <button onClick={() => setVista('admin')} className="flex items-center gap-2 bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-semibold hover:bg-red-700 transition-colors">
              ← Volver al Admin
            </button>
            <p className="font-semibold text-gray-900 text-sm">Vista Técnico — {user?.name}</p>
          </div>
        </header>
        <div className="max-w-4xl mx-auto px-4 py-4">
          {loadingTecnico ? (
            <div className="flex justify-center py-12"><div className="animate-spin h-10 w-10 border-4 border-red-500 border-t-transparent rounded-full" /></div>
          ) : ordenesTecnico.length === 0 ? (
            <div className="bg-white rounded-2xl p-8 text-center text-gray-400 mt-4">
              <p className="text-4xl mb-3">📋</p>
              <p className="font-medium">No hay órdenes asignadas</p>
            </div>
          ) : (
            <>
              {pendientes.length > 0 && (
                <div className="mb-4">
                  <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-1">Pendientes ({pendientes.length})</p>
                  <div className="space-y-3">
                    {pendientes.map(o => (
                      <div key={o.id} className={`bg-white rounded-xl shadow-sm border-l-4 ${prioColor[o.priority] || 'border-l-gray-300'} p-4`}>
                        <div className="flex items-start justify-between mb-1">
                          <p className="font-semibold text-gray-900 text-sm">{o.clientName || o.customer?.business_name || 'Sin cliente'}</p>
                          <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${statusColor[o.status] || 'bg-gray-100 text-gray-600'}`}>{normalizeStatus(o.status)}</span>
                        </div>
                        <p className="text-sm text-gray-600 mb-2">{o.problem || o.title}</p>
                        {o.address && <p className="text-xs text-gray-400">📍 {o.address}</p>}
                        <button onClick={() => router.push(`/parte?id=${o.id}`)} className="mt-3 w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
                          Crear Parte →
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}
              {completadas.length > 0 && (
                <div>
                  <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-1">Completadas ({completadas.length})</p>
                  <div className="space-y-2">
                    {completadas.map(o => (
                      <div key={o.id} className="bg-white/10 rounded-xl p-3 flex items-center justify-between">
                        <p className="text-sm text-gray-300">{o.clientName || o.customer?.business_name}</p>
                        <span className="text-xs bg-green-900/50 text-green-300 px-2 py-0.5 rounded-full">Completado</span>
                      </div>
                    ))}
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-red-600 text-white px-4 py-4 flex items-center justify-between shadow-lg">
        <div className="flex items-center gap-3">
          <img src="/icon.svg" alt="Logo" className="w-8 h-8 bg-white rounded-lg p-1" />
          <div><h1 className="font-bold text-lg leading-tight">SerTecApp Admin</h1><p className="text-red-100 text-xs">{user?.name}</p></div>
        </div>
        <button onClick={logout} className="text-red-100 hover:text-white text-sm font-medium">Salir</button>
      </header>

      <main className="p-4 max-w-4xl mx-auto">
        <div className="grid grid-cols-2 gap-3 mb-4 mt-4">
          {[{ label: 'Órdenes', value: stats.ordenes, icon: '📋' }, { label: 'Clientes', value: stats.clientes, icon: '👥' },
            { label: 'Repuestos', value: stats.repuestos, icon: '🔩' }, { label: 'Sistema', value: '✓', icon: '🟢' }].map((s) => (
            <div key={s.label} className="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-3 shadow-sm">
              <span className="text-2xl">{s.icon}</span>
              <div><p className="text-2xl font-bold text-gray-800">{loading ? '...' : s.value}</p><p className="text-xs text-gray-500">{s.label}</p></div>
            </div>
          ))}
        </div>

        {/* Accesos principales */}
        <div className="grid grid-cols-2 gap-3 mb-3">
          <button onClick={() => verVistasTecnico(user?.id)} className="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-all text-left w-full">
            <span className="text-2xl">👷</span>
            <div><p className="font-semibold text-gray-800 text-sm">Vista Técnico</p><p className="text-xs text-gray-400">Ver mis órdenes</p></div>
          </button>
          <button onClick={() => router.push('/admin/gestion')} className="bg-white border border-gray-200 rounded-2xl p-4 flex items-center gap-3 shadow-sm hover:shadow-md transition-all text-left w-full">
            <span className="text-2xl">👤</span>
            <div><p className="font-semibold text-gray-800 text-sm">Usuarios</p><p className="text-xs text-gray-400">Gestionar técnicos</p></div>
          </button>
        </div>

        {/* Ver como técnico específico */}
        {tecnicos.length > 0 && (
          <div className="mb-4">
            <p className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2 px-1">Ver como técnico</p>
            <div className="flex gap-2 flex-wrap">
              {tecnicos.map(t => (
                <button key={t.id} onClick={() => verVistasTecnico(t.id)} className="bg-white border border-gray-200 rounded-xl px-3 py-2 text-sm font-medium text-gray-700 hover:bg-red-50 hover:border-red-300 hover:text-red-700 transition-all shadow-sm">
                  👷 {t.name}
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Lista órdenes */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <h2 className="font-semibold text-gray-800">Órdenes de Trabajo</h2>
            <button onClick={abrirModal} className="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">+ Nueva</button>
          </div>
          {loading ? <div className="p-8 text-center text-gray-400">Cargando...</div>
            : ordenes.length === 0 ? <div className="p-8 text-center text-gray-400">No hay órdenes</div>
            : <div className="divide-y divide-gray-50">
              {ordenes.map((o) => (
                <div key={o.id} className="px-4 py-3 flex items-center justify-between hover:bg-gray-50 cursor-pointer" onClick={() => router.push('/admin/orden?id=' + String(o.id))}>
                  <div>
                    <p className="font-medium text-gray-800 text-sm">#{o.id} — {o.customer?.business_name || o.customer?.full_name || 'Sin cliente'}</p>
                    <p className="text-xs text-gray-500">{o.title}</p>
                    {o.assigned_tech && <p className="text-xs text-blue-500">👷 {o.assigned_tech.name}</p>}
                  </div>
                  <span className={`text-xs px-2 py-1 rounded-full font-medium ${statusColor[o.status] || 'bg-gray-100 text-gray-600'}`}>{normalizeStatus(o.status)}</span>
                </div>
              ))}
            </div>}
        </div>
      </main>

      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div className="bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl">
              <h2 className="font-bold text-gray-800 text-lg">Nueva Orden</h2>
              <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
                <select value={form.customer_id} onChange={e => setForm(f => ({ ...f, customer_id: e.target.value }))} style={{color:'#111827'}} className={selectClass}>
                  <option value="">Seleccionar cliente...</option>
                  {clientes.map(c => <option key={c.id} value={c.id} style={{color:'#111827'}}>{nombreCliente(c)}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Equipo</label>
                <select value={form.equipment_id} onChange={e => setForm(f => ({ ...f, equipment_id: e.target.value }))} disabled={!form.customer_id} style={{color:'#111827'}} className={`${selectClass} disabled:bg-gray-50 disabled:text-gray-400`}>
                  <option value="">{form.customer_id ? (equiposFiltrados.length === 0 ? 'Sin equipos' : 'Seleccionar equipo...') : 'Primero seleccioná un cliente'}</option>
                  {equiposFiltrados.map(e => <option key={e.id} value={e.id} style={{color:'#111827'}}>{e.brand} {e.model}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))} placeholder="Ej: Reparación cinta caminadora" style={{color:'#111827'}} className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-red-500 bg-white" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} rows={3} placeholder="Detalles del problema..." style={{color:'#111827'}} className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-red-500 resize-none bg-white" />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                  <select value={form.priority} onChange={e => setForm(f => ({ ...f, priority: e.target.value }))} style={{color:'#111827'}} className={selectClass}>
                    {prioridades.map(p => <option key={p.value} value={p.value} style={{color:'#111827'}}>{p.label}</option>)}
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Técnico</label>
                  <select value={form.assigned_tech_id} onChange={e => setForm(f => ({ ...f, assigned_tech_id: e.target.value }))} style={{color:'#111827'}} className={selectClass}>
                    <option value="">Sin asignar</option>
                    {tecnicos.map(t => <option key={t.id} value={t.id} style={{color:'#111827'}}>{t.name}</option>)}
                  </select>
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Fecha programada</label>
                <input type="date" value={form.scheduled_date} onChange={e => setForm(f => ({ ...f, scheduled_date: e.target.value }))} style={{color:'#111827'}} className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm focus:ring-2 focus:ring-red-500 bg-white" />
              </div>
              <div className="flex items-center gap-3">
                <input type="checkbox" id="firma" checked={form.requires_signature} onChange={e => setForm(f => ({ ...f, requires_signature: e.target.checked }))} className="w-4 h-4 text-red-600 rounded" />
                <label htmlFor="firma" className="text-sm text-gray-700">Requiere firma del cliente</label>
              </div>
              {formError && <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{formError}</div>}
              <button onClick={crearOrden} disabled={saving} className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl transition-all disabled:opacity-50">
                {saving ? 'Creando...' : 'Crear Orden'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
