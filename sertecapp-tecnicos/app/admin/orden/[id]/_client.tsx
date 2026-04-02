'use client';

import { useEffect, useState, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { API_URL } from '../../../../lib/config';

const sel = "w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500";
const inp = "w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500";

const normalizeStatus = (s: string) => {
  const map: Record<string, string> = {
    completed: 'completado', in_progress: 'en_progreso',
    pending: 'pendiente', cancelled: 'cancelado',
  };
  return map[s] ?? s;
};

const prioridades = [
  { value: 'low', label: 'Baja' }, { value: 'medium', label: 'Media' },
  { value: 'high', label: 'Alta' }, { value: 'urgent', label: 'Urgente' },
];
const estados = [
  { value: 'pendiente', label: 'Pendiente' }, { value: 'en_progreso', label: 'En Progreso' },
  { value: 'completado', label: 'Completado' }, { value: 'cancelado', label: 'Cancelado' },
];
const statusColor: Record<string, string> = {
  pendiente: 'bg-yellow-100 text-yellow-800', en_progreso: 'bg-blue-100 text-blue-800',
  completado: 'bg-green-100 text-green-800', completed: 'bg-green-100 text-green-800',
  cancelado: 'bg-red-100 text-red-800',
};

const logIcon: Record<string, string> = {
  orden_creada: '🆕', orden_editada: '✏️', estado_cambiado: '🔄',
  tecnico_asignado: '👷', parte_completado: '✅', parte_actualizado: '📝',
};

function OrdenEditContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const [orderId, setOrderId] = useState('');
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
  const [logs, setLogs] = useState<any[]>([]);
  const [parte, setParte] = useState<any>(null);
  const [form, setForm] = useState({
    customer_id: '', equipment_id: '', title: '', description: '',
    priority: 'medium', status: 'pendiente', assigned_tech_id: '',
    scheduled_date: '', requires_signature: false,
  });

  useEffect(() => {
    const id = searchParams.get('id');
    if (id) setOrderId(id);
  }, [searchParams]);

  useEffect(() => {
    if (!orderId) return;
    const t = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!t || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    const roles: string[] = u?.roles || [];
    if (!roles.includes('administrador') && !roles.includes('admin')) { router.push('/ordenes'); return; }
    setToken(t);
    loadAll(t, orderId);
  }, [orderId]);

  const hd = (t: string) => ({
    'Authorization': 'Bearer ' + t, 'Accept': 'application/json', 'Content-Type': 'application/json'
  });

  const loadAll = async (t: string, id: string) => {
    setLoading(true);
    try {
      const [ordenRes, clientesRes, tecnicosRes, equiposRes, logsRes, parteRes] = await Promise.all([
        fetch(`${API_URL}/api/v1/work-orders/${id}`, { headers: hd(t) }),
        fetch(`${API_URL}/api/v1/customers?per_page=500`, { headers: hd(t) }),
        fetch(`${API_URL}/api/v1/users?per_page=100`, { headers: hd(t) }),
        fetch(`${API_URL}/api/v1/equipments?per_page=500`, { headers: hd(t) }),
        fetch(`${API_URL}/api/v1/work-orders/${id}/logs`, { headers: hd(t) }),
        fetch(`${API_URL}/api/v1/partes/${id}`, { headers: hd(t) }),
      ]);

      if (clientesRes.ok) { const d = await clientesRes.json(); setClientes(d.data || []); }
      if (tecnicosRes.ok) { const d = await tecnicosRes.json(); setTecnicos(d.data || []); }
      if (equiposRes.ok) { const d = await equiposRes.json(); setEquipos(d.data || []); }
      if (logsRes.ok) { const d = await logsRes.json(); setLogs(d.data || []); }
      if (parteRes.ok) { const d = await parteRes.json(); if (d.data) setParte(d.data); }

      if (ordenRes.ok) {
        const d = await ordenRes.json();
        const o = d.data || d;
        setOrden(o);
        const customerId = String(o.customer?.id || o.customer_id || '');
        const equipmentId = String(o.equipment?.id || o.equipment_id || '');
        const techId = String(o.assigned_tech?.id || o.assigned_tech_id || '');
        setForm({
          customer_id: customerId, equipment_id: equipmentId,
          title: o.title || '', description: o.description || '',
          priority: o.priority || 'medium',
          status: normalizeStatus(o.status || 'pendiente'),
          assigned_tech_id: techId,
          scheduled_date: o.scheduled_date ? o.scheduled_date.substring(0, 10) : '',
          requires_signature: !!o.requires_signature,
        });
      } else { setError('No se pudo cargar la orden'); }
    } catch { setError('Error de conexion'); }
    finally { setLoading(false); }
  };

  useEffect(() => {
    if (form.customer_id) {
      setEquiposFiltrados(equipos.filter(e => String(e.customer_id) === String(form.customer_id)));
    } else { setEquiposFiltrados([]); }
  }, [form.customer_id, equipos]);

  const guardar = async () => {
    if (!form.customer_id || !form.title.trim()) { setError('Cliente y titulo son obligatorios'); return; }
    setSaving(true); setError(''); setSuccess('');
    try {
      const body: any = {
        customer_id: parseInt(form.customer_id), title: form.title,
        description: form.description, priority: form.priority,
        requires_signature: form.requires_signature,
      };
      if (form.equipment_id) body.equipment_id = parseInt(form.equipment_id);
      if (form.assigned_tech_id) body.assigned_tech_id = parseInt(form.assigned_tech_id);
      if (form.scheduled_date) body.scheduled_date = form.scheduled_date;

      const res = await fetch(`${API_URL}/api/v1/work-orders/${orderId}`, {
        method: 'PUT', headers: hd(token), body: JSON.stringify(body),
      });
      if (form.status !== normalizeStatus(orden?.status || 'pendiente')) {
        await fetch(`${API_URL}/api/v1/work-orders/${orderId}/change-status`, {
          method: 'POST', headers: hd(token), body: JSON.stringify({ status: form.status }),
        });
      }
      if (res.ok) {
        setSuccess('Orden actualizada ✓');
        // Recargar logs
        const logsRes = await fetch(`${API_URL}/api/v1/work-orders/${orderId}/logs`, { headers: hd(token) });
        if (logsRes.ok) { const d = await logsRes.json(); setLogs(d.data || []); }
        setTimeout(() => router.push('/admin'), 1500);
      } else {
        const d = await res.json(); setError(d.message || 'Error al guardar');
      }
    } catch { setError('Error de conexion'); }
    finally { setSaving(false); }
  };

  const nombreCliente = (c: any) => c.business_name || c.full_name || '';

  const formatDate = (iso: string) => {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
      + ' ' + d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
  };

  if (!orderId || loading) return (
    <div className="min-h-screen bg-gray-50 flex items-center justify-center">
      <div className="animate-spin h-8 w-8 border-4 border-red-500 border-t-transparent rounded-full"></div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-red-600 text-white px-4 py-4 flex items-center gap-3 shadow-lg">
        <button onClick={() => router.push('/admin')} className="text-white text-xl font-bold">&#8592;</button>
        <div>
          <h1 className="font-bold text-lg">Orden #{orderId}</h1>
          <p className="text-red-100 text-xs">{orden?.customer?.business_name || orden?.customer?.full_name || ''}</p>
        </div>
        {orden?.status && (
          <span className={'ml-auto text-xs px-3 py-1 rounded-full font-medium ' + (statusColor[orden.status] || 'bg-gray-100 text-gray-600')}>
            {normalizeStatus(orden.status)}
          </span>
        )}
      </header>

      <main className="p-4 max-w-lg mx-auto space-y-4 mt-4">

        {/* Formulario edición */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
          <h2 className="font-semibold text-gray-800 text-sm uppercase tracking-wide">Datos de la Orden</h2>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select value={form.status} onChange={e => setForm(f => ({ ...f, status: e.target.value }))} style={{color:'#111827'}} className={sel}>
              {estados.map(s => <option key={s.value} value={s.value} style={{color:'#111827'}}>{s.label}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Cliente *</label>
            <select value={form.customer_id} onChange={e => setForm(f => ({ ...f, customer_id: e.target.value }))} style={{color:'#111827'}} className={sel}>
              <option value="">Seleccionar cliente...</option>
              {clientes.map(c => <option key={c.id} value={c.id} style={{color:'#111827'}}>{nombreCliente(c)}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Equipo</label>
            <select value={form.equipment_id} onChange={e => setForm(f => ({ ...f, equipment_id: e.target.value }))} disabled={!form.customer_id} style={{color:'#111827'}} className={sel + ' disabled:bg-gray-50'}>
              <option value="">{form.customer_id ? (equiposFiltrados.length === 0 ? 'Sin equipos' : 'Seleccionar...') : 'Primero selecciona cliente'}</option>
              {equiposFiltrados.map(e => <option key={e.id} value={e.id} style={{color:'#111827'}}>{e.brand?.name || e.brand} {e.model?.name || e.model}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Título *</label>
            <input value={form.title} onChange={e => setForm(f => ({ ...f, title: e.target.value }))} style={{color:'#111827'}} className={inp} placeholder="Título de la orden" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
            <textarea value={form.description} onChange={e => setForm(f => ({ ...f, description: e.target.value }))} rows={3} style={{color:'#111827'}} className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500 resize-none" placeholder="Detalles..." />
          </div>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
              <select value={form.priority} onChange={e => setForm(f => ({ ...f, priority: e.target.value }))} style={{color:'#111827'}} className={sel}>
                {prioridades.map(p => <option key={p.value} value={p.value} style={{color:'#111827'}}>{p.label}</option>)}
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">Técnico</label>
              <select value={form.assigned_tech_id} onChange={e => setForm(f => ({ ...f, assigned_tech_id: e.target.value }))} style={{color:'#111827'}} className={sel}>
                <option value="">Sin asignar</option>
                {tecnicos.map(t => <option key={t.id} value={t.id} style={{color:'#111827'}}>{t.name}</option>)}
              </select>
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Fecha programada</label>
            <input type="date" value={form.scheduled_date} onChange={e => setForm(f => ({ ...f, scheduled_date: e.target.value }))} style={{color:'#111827'}} className={inp} />
          </div>
          <div className="flex items-center gap-3">
            <input type="checkbox" id="firma" checked={form.requires_signature} onChange={e => setForm(f => ({ ...f, requires_signature: e.target.checked }))} className="w-4 h-4 text-red-600 rounded" />
            <label htmlFor="firma" className="text-sm text-gray-700">Requiere firma del cliente</label>
          </div>
          {error && <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>}
          {success && <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{success}</div>}
          <button onClick={guardar} disabled={saving} className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl disabled:opacity-50 transition-colors">
            {saving ? 'Guardando...' : 'Guardar Cambios'}
          </button>
          <button onClick={() => router.push('/admin')} className="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl transition-colors">
            Cancelar
          </button>
        </div>

        {/* Parte técnico completado */}
        {parte && (
          <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
            <h2 className="font-semibold text-gray-800 text-sm uppercase tracking-wide flex items-center gap-2">
              ✅ Parte Completado
            </h2>
            <div>
              <p className="text-xs font-medium text-gray-500 mb-1">Técnico</p>
              <p className="text-sm text-gray-900">{parte.tecnico_name || '-'}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-gray-500 mb-1">Diagnóstico</p>
              <p className="text-sm text-gray-900 whitespace-pre-wrap">{parte.diagnostico || parte.diagnosis}</p>
            </div>
            <div>
              <p className="text-xs font-medium text-gray-500 mb-1">Trabajo Realizado</p>
              <p className="text-sm text-gray-900 whitespace-pre-wrap">{parte.trabajo_realizado || parte.work_done}</p>
            </div>
            {parte.repuestos && parte.repuestos.length > 0 && (
              <div>
                <p className="text-xs font-medium text-gray-500 mb-1">Repuestos Usados</p>
                <div className="space-y-1">
                  {parte.repuestos.map((r: any, i: number) => (
                    <div key={i} className="flex justify-between text-sm text-gray-900 bg-gray-50 px-3 py-1.5 rounded-lg">
                      <span>{r.nombre}</span>
                      <span className="text-gray-500">×{r.cantidad}</span>
                    </div>
                  ))}
                </div>
              </div>
            )}
            {(parte.firma_base64 || parte.signature) && (
              <div>
                <p className="text-xs font-medium text-gray-500 mb-2">Firma del Cliente</p>
                <img src={parte.firma_base64 || parte.signature} alt="Firma" className="border border-gray-200 rounded-lg max-w-full bg-white p-2" />
              </div>
            )}
            <p className="text-xs text-gray-400">Completado: {formatDate(parte.created_at)}</p>
          </div>
        )}

        {/* Historial de auditoría */}
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
          <h2 className="font-semibold text-gray-800 text-sm uppercase tracking-wide mb-4">📋 Historial</h2>
          {logs.length === 0 ? (
            <p className="text-sm text-gray-400 text-center py-4">Sin registros todavía</p>
          ) : (
            <div className="space-y-3">
              {logs.map((log: any, i: number) => (
                <div key={i} className="flex gap-3 items-start">
                  <span className="text-lg flex-shrink-0 mt-0.5">{logIcon[log.log_type] || '📌'}</span>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm text-gray-900">{log.message}</p>
                    <div className="flex items-center gap-2 mt-0.5 flex-wrap">
                      <span className="text-xs text-gray-400">{formatDate(log.created_at)}</span>
                      {log.lat && log.lng && (
                        <a
                          href={`https://maps.google.com/?q=${log.lat},${log.lng}`}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="text-xs text-blue-500 hover:underline"
                        >
                          📍 Ver ubicación
                        </a>
                      )}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

      </main>
    </div>
  );
}

export default function OrdenEditClient() {
  return (
    <Suspense fallback={
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin h-8 w-8 border-4 border-red-500 border-t-transparent rounded-full"></div>
      </div>
    }>
      <OrdenEditContent />
    </Suspense>
  );
}
