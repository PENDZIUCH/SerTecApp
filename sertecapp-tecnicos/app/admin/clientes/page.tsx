'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { API_URL } from '../../../lib/config';

interface Customer {
  id: number; customer_type: string; business_name: string | null;
  first_name: string | null; last_name: string | null; full_name: string;
  email: string | null; phone: string | null; tax_id: string | null;
  full_address: string; is_active: boolean; created_at: string;
}

const inp = "w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500 text-gray-900";

export default function ClientesPage() {
  const router = useRouter();
  const [token, setToken] = useState('');
  const [customers, setCustomers] = useState<Customer[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [meta, setMeta] = useState({ total: 0, last_page: 1 });
  const [showModal, setShowModal] = useState(false);
  const [editCustomer, setEditCustomer] = useState<Customer | null>(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [form, setForm] = useState({
    customer_type: 'company', business_name: '', first_name: '', last_name: '',
    email: '', phone: '', tax_id: '', address: '', city: '', notes: '',
  });

  useEffect(() => {
    const t = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!t || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    if (!u?.roles?.includes('administrador') && !u?.roles?.includes('admin')) { router.push('/ordenes'); return; }
    setToken(t);
  }, []);

  const loadCustomers = useCallback(async (t: string, s: string, p: number) => {
    setLoading(true);
    try {
      const params = new URLSearchParams({ per_page: '20', page: String(p) });
      if (s) params.set('search', s);
      const res = await fetch(`${API_URL}/api/v1/customers?${params}`, {
        headers: { 'Authorization': `Bearer ${t}`, 'Accept': 'application/json' }
      });
      if (res.ok) {
        const d = await res.json();
        setCustomers(d.data || []);
        setMeta({ total: d.meta?.total || 0, last_page: d.meta?.last_page || 1 });
      }
    } catch { setError('Error cargando clientes'); }
    finally { setLoading(false); }
  }, []);

  useEffect(() => {
    if (token) loadCustomers(token, search, page);
  }, [token, page]);

  // Búsqueda con debounce
  useEffect(() => {
    if (!token) return;
    const timer = setTimeout(() => { setPage(1); loadCustomers(token, search, 1); }, 400);
    return () => clearTimeout(timer);
  }, [search]);

  const hd = () => ({ 'Authorization': `Bearer ${token}`, 'Accept': 'application/json', 'Content-Type': 'application/json' });

  const abrirNuevo = () => {
    setEditCustomer(null);
    setForm({ customer_type: 'company', business_name: '', first_name: '', last_name: '', email: '', phone: '', tax_id: '', address: '', city: '', notes: '' });
    setError(''); setShowModal(true);
  };

  const abrirEditar = (c: Customer) => {
    setEditCustomer(c);
    // Detectar tipo real basado en datos disponibles
    const tipo = c.business_name ? 'company' : (c.first_name ? 'individual' : c.customer_type || 'company');
    // Extraer address y city del full_address
    const parts = (c.full_address || '').split(',').map(s => s.trim()).filter(s => s && s !== 'Argentina');
    setForm({
      customer_type: tipo,
      business_name: c.business_name || '',
      first_name: c.first_name || '',
      last_name: c.last_name || '',
      email: c.email || '',
      phone: c.phone || '',
      tax_id: c.tax_id || '',
      address: parts[0] || '',
      city: parts[1] || '',
      notes: '',
    });
    setError(''); setShowModal(true);
  };

  const guardar = async () => {
    const nombre = form.business_name || form.first_name;
    if (!nombre.trim()) { setError('Ingresá razón social o nombre'); return; }
    setSaving(true); setError('');
    try {
      const url = editCustomer ? `${API_URL}/api/v1/customers/${editCustomer.id}` : `${API_URL}/api/v1/customers`;
      const method = editCustomer ? 'PUT' : 'POST';
      const res = await fetch(url, { method, headers: hd(), body: JSON.stringify(form) });
      const data = await res.json();
      if (res.ok) { setShowModal(false); loadCustomers(token, search, page); }
      else { setError(data.message || 'Error al guardar'); }
    } catch { setError('Error de conexión'); }
    finally { setSaving(false); }
  };

  const nombre = (c: Customer) => c.business_name || c.full_name || `${c.first_name || ''} ${c.last_name || ''}`.trim() || 'Sin nombre';

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-red-600 text-white px-4 py-4 flex items-center gap-3 shadow-lg sticky top-0 z-10">
        <button onClick={() => router.push('/admin')} className="text-white text-xl font-bold">&#8592;</button>
        <div className="flex-1">
          <h1 className="font-bold text-lg">Clientes</h1>
          <p className="text-red-100 text-xs">{meta.total} registros</p>
        </div>
        <button onClick={abrirNuevo} className="bg-white text-red-600 font-semibold text-sm px-4 py-2 rounded-xl hover:bg-red-50 transition-colors">
          + Nuevo
        </button>
      </header>

      {/* Buscador */}
      <div className="px-4 pt-4 pb-2 max-w-lg mx-auto">
        <div className="relative">
          <input value={search} onChange={e => setSearch(e.target.value)}
            placeholder="Buscar por nombre, email o teléfono..."
            className="w-full px-4 py-3 pl-10 border border-gray-300 rounded-2xl text-sm bg-white focus:ring-2 focus:ring-red-500 text-gray-900 shadow-sm" />
          <span className="absolute left-3 top-3.5 text-gray-400 text-base">🔍</span>
          {search && <button onClick={() => setSearch('')} className="absolute right-3 top-3 text-gray-400 hover:text-gray-600 text-lg">×</button>}
        </div>
      </div>

      <main className="px-4 pb-8 max-w-lg mx-auto">
        {loading ? (
          <div className="flex justify-center py-12"><div className="animate-spin h-8 w-8 border-4 border-red-500 border-t-transparent rounded-full" /></div>
        ) : customers.length === 0 ? (
          <div className="text-center py-12 text-gray-400">
            <p className="text-4xl mb-3">🏢</p>
            <p>{search ? 'No se encontraron clientes' : 'No hay clientes'}</p>
          </div>
        ) : (
          <>
            <div className="space-y-2 mt-2">
              {customers.map(c => (
                <div key={c.id} className="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <p className="font-semibold text-gray-900 text-sm truncate">{nombre(c)}</p>
                    {c.tax_id && <p className="text-xs text-gray-500">CUIT: {c.tax_id}</p>}
                    {c.email && <p className="text-xs text-gray-400 truncate">{c.email}</p>}
                    {c.phone && <p className="text-xs text-gray-400">{c.phone}</p>}
                    {c.full_address && <p className="text-xs text-gray-400 truncate">📍 {c.full_address}</p>}
                  </div>
                  <button onClick={() => abrirEditar(c)} className="ml-3 text-xs bg-gray-100 hover:bg-gray-200 text-gray-600 px-3 py-1.5 rounded-lg flex-shrink-0 transition-colors">
                    Editar
                  </button>
                </div>
              ))}
            </div>

            {/* Paginación */}
            {meta.last_page > 1 && (
              <div className="flex items-center justify-center gap-3 mt-4 pt-4 border-t border-gray-200">
                <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                  className="px-4 py-2 text-sm bg-white border border-gray-300 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                  ← Anterior
                </button>
                <span className="text-sm text-gray-600">{page} / {meta.last_page}</span>
                <button onClick={() => setPage(p => Math.min(meta.last_page, p + 1))} disabled={page === meta.last_page}
                  className="px-4 py-2 text-sm bg-white border border-gray-300 rounded-xl disabled:opacity-40 hover:bg-gray-50 transition-colors">
                  Siguiente →
                </button>
              </div>
            )}
          </>
        )}
      </main>

      {/* Modal crear/editar cliente */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div className="bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl">
              <h2 className="font-bold text-gray-800 text-lg">{editCustomer ? 'Editar Cliente' : 'Nuevo Cliente'}</h2>
              <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                <select value={form.customer_type} onChange={e => setForm(f => ({ ...f, customer_type: e.target.value }))} className={inp}>
                  <option value="company">Empresa</option>
                  <option value="individual">Persona</option>
                </select>
              </div>
              {form.customer_type === 'company' ? (
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                  <input value={form.business_name} onChange={e => setForm(f => ({ ...f, business_name: e.target.value }))} className={inp} placeholder="Nombre de la empresa" />
                </div>
              ) : (
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input value={form.first_name} onChange={e => setForm(f => ({ ...f, first_name: e.target.value }))} className={inp} placeholder="Nombre" />
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Apellido</label>
                    <input value={form.last_name} onChange={e => setForm(f => ({ ...f, last_name: e.target.value }))} className={inp} placeholder="Apellido" />
                  </div>
                </div>
              )}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">CUIT / DNI</label>
                <input value={form.tax_id} onChange={e => setForm(f => ({ ...f, tax_id: e.target.value }))} className={inp} placeholder="XX-XXXXXXXX-X" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} className={inp} placeholder="contacto@empresa.com" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input value={form.phone} onChange={e => setForm(f => ({ ...f, phone: e.target.value }))} className={inp} placeholder="+54 11 ..." />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                  <input value={form.address} onChange={e => setForm(f => ({ ...f, address: e.target.value }))} className={inp} placeholder="Calle 1234" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                  <input value={form.city} onChange={e => setForm(f => ({ ...f, city: e.target.value }))} className={inp} placeholder="Buenos Aires" />
                </div>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Notas</label>
                <textarea value={form.notes} onChange={e => setForm(f => ({ ...f, notes: e.target.value }))} rows={2} className="w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500 resize-none text-gray-900" placeholder="Observaciones..." />
              </div>
              {error && <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>}
              <button onClick={guardar} disabled={saving} className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl disabled:opacity-50 transition-colors">
                {saving ? 'Guardando...' : editCustomer ? 'Guardar Cambios' : 'Crear Cliente'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
