'use client';

import { useEffect, useState } from 'react';
import { useRouter, useParams } from 'next/navigation';

const API = 'https://sertecapp.pendziuch.com';
const selectClass = "w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500 focus:border-transparent";
const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500 focus:border-transparent";

const prioridades = [
  { value: 'low', label: 'Baja' },
  { value: 'medium', label: 'Media' },
  { value: 'high', label: 'Alta' },
  { value: 'urgent', label: 'Urgente' },
];

const estados = [
  { value: 'pendiente', label: 'Pendiente' },
  { value: 'en_progreso', label: 'En Progreso' },
  { value: 'completado', label: 'Completado' },
  { value: 'cancelado', label: 'Cancelado' },
];

const statusColor: Record<string, string> = {
  pendiente: 'bg-yellow-100 text-yellow-800',
  en_progreso: 'bg-blue-100 text-blue-800',
  completado: 'bg-green-100 text-green-800',
  cancelado: 'bg-red-100 text-red-800',
};

export default function OrdenEditClient() {
  const router = useRouter();
  const params = useParams();
  const [token, setToken] = useState('');
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [orden, setOrden] = useState<any>(null);
  const [clientes, setClientes] = useState<any[]>([]);
  const [tecnicos, setTecnicos] = useState<any[]>([]);
  const [equipos, setEquipos] = useState<any[]>([]);
  const [equiposFiltrados, setEquiposFiltrados] = useState<any[]>([]);

  const [form, setForm] = useState({
    customer_id: '',
    equipment_id: '',
    title: '',
    description: '',
    priority: 'medium',
    status: 'pendiente',
    assigned_tech_id: '',
    scheduled_date: '',
    requires_signature: false,
  });

  useEffect(() => {
    const t = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!t || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    const roles: string[] = u?.roles || [];
    if (!roles.includes('administrador') && !roles.includes('admin')) { router.push('/ordenes'); return; }
    setToken(t);
    loadAll(t);
  }, []);

  const hd = (t: string) => ({ 'Authorization': `Bearer ${t}`, 'Accept': 'application/json', 'Content-Type': 'application/json' });

  const loadAll = async (t: string) => {
    try {
      const id = params.id as string;
      const [ordenRes, clientesRes, tecnicosRes, equiposRes] = await Promise.all([
        fetch(`${API}/api/v1/work-orders/${id}`, { headers: hd(t) }),
        fetch(`${API}/api/v1/customers?per_page=500`, { headers: hd(t) }),
        fetch(`${API}/api/v1/users?per_page=100`, { headers: hd(t) }),
        fetch(`${API}/api/v1/equipments?per_page=500`, { headers: hd(t) }),
      ]);
      if (clientesRes.ok) { const d = await clientesRes.json(); setClientes(d.data || []); }
      if (tecnicosRes.ok) { const d = await tecnicosRes.json(); setTecnicos(d.data || []); }
      if (equiposRes.ok) { const d = await equiposRes.json(); setEquipos(d.data || []); }
      if (ordenRes.ok) {
        const d = await ordenRes.json();
        const o = d.data || d;
        setOrden(o);
        setForm({
          customer_id: String(o.customer_id || ''),
          equipment_id: String(o.equipment_id || ''),
          title: o.title || '',
          description: o.description || '',
          priority: o.priority || 'medium',
          status: o.status || 'pendiente',
          assigned_tech_id: String(o.assigned_tech_id || ''),
          scheduled_date: o.scheduled_date ? o.scheduled_date.substring(0, 10) : '',
          requires_signature: o.requires_signature || false,
        });
      }
    } catch (e) { setError('Error cargando datos'); }
    finally { setLoading(false); }
  };

  useEffect(() => {
    if (form.customer_id) {
      setEquiposFiltrados(equipos.filter(e => String(e.customer_id) === String(form.customer_id)));
    } else { setEquiposFiltrados([]); }
  }, [form.customer_id, equipos]);

  const guardar = async () => {
    if (!form.customer_id || !form.title.trim()) { setError('Cliente y título son obligatorios'); return; }
    setSaving(true); setError(''); setSuccess('');
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

      const id = params.id as string;

      // Actualizar datos
      const res = await fetch(`${API}/api/v1/work-orders/${id}`, {
        method: 'PUT', headers: hd(token), body: JSON.stringify(body),
      });

      // Cambiar estado si cambió
      if (form.status !== orden?.status) {
        await fetch(`${API}/api/v1/work-orders/${id}/change-status`, {
          method: 'POST', headers: hd(token), body: JSON.stringify({ status: form.status }),
        });
      }

      if (res.ok) { setSuccess('Orden actualizada correctamente'); setTimeout(() => router.push('/admin'), 1500); }
      else { const d = await res.json(); setError(d.message || 'Error al guardar'); }
    } catch (e) { setError('Error de conexión'); }
    finally { setSaving(false); }
  };

  const nombreCliente = (c: any) => c.business_name || c.full_name || '';

  if (loading) return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center">
      <div className="text-center text-gray-400">
        <div className="animate-spin h-8 w-8 border-4 border-red-500 border-t-transparent rounded-full mx-auto mb-2"></div>
        Cargando...
      </div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-red-600 text-white px-4 py-4 flex items-center gap-3 shadow-lg">
        <button onClick={() => router.push('/admin')} className="text-white hover:text-red-100 text-xl font-bold">←</button>
        <div>
          <h1 className="font-bold text-lg leading-tight">Editar Orden #{params.id}</h1>
          <p className="text-red-100 text-xs">{orden?.customer?.business_name || orden?.customer?.full_name}</p>
        </div>
        {orden?.status && (
          <span className={`ml-auto text-xs px-3 py-1 rounded-full font-medium ${statusColor[orden.status] || 'bg-gray-100 text-gray-600'}`}>
            {orden.status}
          </span>
        )}
      </header>

      <main className="p-4 max-w-lg mx-auto">
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-4 space-y-4">

          {/* Estado */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select value={form.status} onChange={e => setForm(f => ({ ...f, status: e.target.value }))}
              style={{ color: '#111827' }} className={selectClass}>
              {estados.map(s => <option key={s.value} value={s.value} style={{ color: '#111827' }}>{s.label}</option>)}
            </select>
          </div>

          {/* Cliente */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
            <select value={form.customer_id} onChange={e => setForm(f => ({ ...f, customer_id: e.target.value }))}
              style={{ color: '#111827' }} className={selectClass}>
              <option value="">Seleccionar cliente...</option>
              {clientes.map(c => <option key={c.id} value={c.id} style={{ color: '#111827' }}>{nombreCliente(c)}</option>)}
            </select>
          </div>

          {/* Equipo */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Equipo</label>
            <select value={form.equipment_id} onChange={e => setForm(f => ({ ...f, equipment_id: e.target.value }))}
              disabled={!form.customer_id} style={{ color: '#111827' }}
              className={`${selectClass} disabled:bg-gray-50`}>
              <option value="">{form.customer_id ? (equiposFiltrados.length === 0 ? 'Sin equipos' : 'Seleccionar equipo...') : 'Primero seleccioná un cliente'}</option>
              {equiposFiltrados.map(e => <option key={e.id} value={e.id} style={{ color: '#111827' }}>{e.brand} {e.model}</option>)}
            </select>
          </div>

          {/* Título */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))}
              style={{ color: '#111827' }} className={inputClass} placeholder="Título de la orden" />
          </div>

          {/* Descripción */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))}
              rows={3} style={{ color: '#111827' }}
              className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
              placeholder="Detalles del problema..." />
          </div>

          {/* Prioridad + Técnico */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
              <select value={form.priority} onChange={e => setForm(f => ({ ...f, priority: e.target.value }))}
                style={{ color: '#111827' }} className={selectClass}>
                {prioridades.map(p => <option key={p.value} value={p.value} style={{ color: '#111827' }}>{p.label}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Técnico</label>
              <select value={form.assigned_tech_id} onChange={e => setForm(f => ({ ...f, assigned_tech_id: e.target.value }))}
                style={{ color: '#111827' }} className={selectClass}>
                <option value="" style={{ color: '#111827' }}>Sin asignar</option>
                {tecnicos.map(t => <option key={t.id} value={t.id} style={{ color: '#111827' }}>{t.name}</option>)}
              </select>
            </div>
          </div>

          {/* Fecha */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Fecha programada</label>
            <input type="date" value={form.scheduled_date} onChange={e => setForm(f => ({ ...f, scheduled_date: e.target.value }))}
              style={{ color: '#111827' }} className={inputClass} />
          </div>

          {/* Requiere firma */}
          <div className="flex items-center gap-3">
            <input type="checkbox" id="firma" checked={form.requires_signature}
              onChange={e => setForm(f => ({ ...f, requires_signature: e.target.checked }))} className="w-4 h-4 text-red-600 rounded" />
            <label htmlFor="firma" className="text-sm text-gray-700">Requiere firma del cliente</label>
          </div>

          {error && <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>}
          {success && <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{success}</div>}

          <button onClick={guardar} disabled={saving}
            className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl transition-all disabled:opacity-50">
            {saving ? 'Guardando...' : 'Guardar Cambios'}
          </button>

          <button onClick={() => router.push('/admin')}
            className="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl transition-all">
            Cancelar
          </button>
        </div>
      </main>
    </div>
  );
}
