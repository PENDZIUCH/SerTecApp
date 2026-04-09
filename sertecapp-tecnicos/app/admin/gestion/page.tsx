'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { API_URL } from '../../../lib/config';

interface User {
  id: number; name: string; email: string; phone: string | null;
  job_title: string | null; is_active: boolean; roles: string[];
  last_login_at: string | null;
}

const inp = "w-full px-3 py-2 border border-gray-300 rounded-xl text-sm bg-white focus:ring-2 focus:ring-red-500 text-gray-900";

export default function GestionPage() {
  const router = useRouter();
  const [token, setToken] = useState('');
  const [users, setUsers] = useState<User[]>([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [editUser, setEditUser] = useState<User | null>(null);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [form, setForm] = useState({
    name: '', email: '', phone: '', job_title: '', password: '', role: '',
  });
  const [showPassword, setShowPassword] = useState(false);
  const [availableRoles, setAvailableRoles] = useState<{id: number; name: string}[]>([]);

  useEffect(() => {
    const t = localStorage.getItem('token');
    const savedUser = localStorage.getItem('user');
    if (!t || !savedUser) { router.push('/'); return; }
    const u = JSON.parse(savedUser);
    const roles: string[] = u?.roles || [];
    if (!roles.includes('administrador') && !roles.includes('admin')) { router.push('/ordenes'); return; }
    setToken(t);
    loadUsers(t);
    loadRoles(t);
  }, []);

  const hd = (t: string) => ({
    'Authorization': 'Bearer ' + t, 'Accept': 'application/json', 'Content-Type': 'application/json'
  });

  const loadUsers = async (t: string) => {
    setLoading(true);
    try {
      const res = await fetch(`${API_URL}/api/v1/users?per_page=100`, { headers: hd(t) });
      if (res.ok) { const d = await res.json(); setUsers(d.data || []); }
    } catch { setError('Error cargando usuarios'); }
    finally { setLoading(false); }
  };

  const loadRoles = async (t: string) => {
    try {
      const res = await fetch(`${API_URL}/api/v1/roles`, { headers: hd(t) });
      if (res.ok) { const d = await res.json(); setAvailableRoles(d.data || []); }
    } catch { console.error('Error cargando roles'); }
  };

  const abrirNuevo = () => {
    setEditUser(null);
    setForm({ name: '', email: '', phone: '', job_title: '', password: '', role: availableRoles[0]?.name || '' });
    setError(''); setSuccess(''); setShowModal(true);
  };

  const abrirEditar = (u: User) => {
    setEditUser(u);
    setForm({ name: u.name, email: u.email, phone: u.phone || '', job_title: u.job_title || '', password: '', role: u.roles[0] || 'técnico' });
    setError(''); setSuccess(''); setShowModal(true);
  };

  const guardar = async () => {
    if (!form.name.trim() || !form.email.trim()) { setError('Nombre y email son obligatorios'); return; }
    if (!form.role.trim()) { setError('Rol es obligatorio'); return; }
    if (!editUser && !form.password.trim()) { setError('La contraseña es obligatoria para nuevos usuarios'); return; }
    if (form.password && form.password.length < 8) { setError('La contraseña debe tener mínimo 8 caracteres'); return; }
    setSaving(true); setError('');
    try {
      const body: any = { name: form.name, email: form.email, phone: form.phone || null, job_title: form.job_title || null, roles: [form.role] };
      if (form.password) body.password = form.password;

      const url = editUser ? `${API_URL}/api/v1/users/${editUser.id}` : `${API_URL}/api/v1/users`;
      const method = editUser ? 'PUT' : 'POST';
      const res = await fetch(url, { method, headers: hd(token), body: JSON.stringify(body) });
      const data = await res.json();
      if (res.ok) {
        setSuccess(editUser ? 'Usuario actualizado ✓' : 'Usuario creado ✓');
        setShowModal(false);
        loadUsers(token);
      } else { setError(data.message || 'Error al guardar'); }
    } catch { setError('Error de conexión'); }
    finally { setSaving(false); }
  };

  const toggleActivo = async (u: User) => {
    try {
      const res = await fetch(`${API_URL}/api/v1/users/${u.id}`, {
        method: 'PUT', headers: hd(token),
        body: JSON.stringify({ name: u.name, email: u.email, is_active: !u.is_active }),
      });
      if (res.ok) loadUsers(token);
    } catch { setError('Error al cambiar estado'); }
  };

  const formatDate = (iso: string | null) => {
    if (!iso) return 'Nunca';
    const d = new Date(iso);
    return d.toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: 'numeric' })
      + ' ' + d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <header className="bg-red-600 text-white px-4 py-4 flex items-center gap-3 shadow-lg">
        <button onClick={() => router.push('/admin')} className="text-white text-xl font-bold">&#8592;</button>
        <div>
          <h1 className="font-bold text-lg">Gestión de Usuarios</h1>
          <p className="text-red-100 text-xs">{users.length} usuarios registrados</p>
        </div>
        <button onClick={abrirNuevo} className="ml-auto bg-white text-red-600 font-semibold text-sm px-4 py-2 rounded-xl hover:bg-red-50 transition-colors">
          + Nuevo
        </button>
      </header>

      <main className="p-4 max-w-lg mx-auto mt-4">
        {loading ? (
          <div className="flex justify-center py-12">
            <div className="animate-spin h-8 w-8 border-4 border-red-500 border-t-transparent rounded-full" />
          </div>
        ) : (
          <div className="space-y-3">
            {users.map(u => (
              <div key={u.id} className={`bg-white rounded-2xl shadow-sm border p-4 ${!u.is_active ? 'opacity-50 border-gray-200' : 'border-gray-100'}`}>
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <p className="font-semibold text-gray-900">{u.name}</p>
                      <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${u.roles[0] === 'administrador' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'}`}>
                        {u.roles[0] === 'administrador' ? 'Admin' : 'Técnico'}
                      </span>
                      {!u.is_active && <span className="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">Inactivo</span>}
                    </div>
                    <p className="text-sm text-gray-500">{u.email}</p>
                    {u.phone && <p className="text-xs text-gray-400">{u.phone}</p>}
                    {u.job_title && <p className="text-xs text-gray-400 italic">{u.job_title}</p>}
                    <p className="text-xs text-gray-400 mt-1">Último acceso: {formatDate(u.last_login_at)}</p>
                  </div>
                  <div className="flex gap-2 ml-3">
                    <button onClick={() => abrirEditar(u)} className="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg transition-colors">
                      Editar
                    </button>
                    <button onClick={() => toggleActivo(u)} className={`text-xs px-3 py-1.5 rounded-lg transition-colors ${u.is_active ? 'bg-red-50 hover:bg-red-100 text-red-600' : 'bg-green-50 hover:bg-green-100 text-green-600'}`}>
                      {u.is_active ? 'Desactivar' : 'Activar'}
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        )}
        {error && !showModal && <div className="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>}
      </main>

      {/* Modal crear/editar */}
      {showModal && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
          <div className="bg-white w-full sm:max-w-md rounded-t-3xl sm:rounded-3xl shadow-2xl max-h-[90vh] overflow-y-auto">
            <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white rounded-t-3xl">
              <h2 className="font-bold text-gray-800 text-lg">{editUser ? 'Editar Usuario' : 'Nuevo Usuario'}</h2>
              <button onClick={() => setShowModal(false)} className="text-gray-400 hover:text-gray-600 text-2xl leading-none">×</button>
            </div>
            <div className="p-6 space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input autoComplete="name" value={form.name} onChange={e => setForm(f => ({ ...f, name: e.target.value }))} className={inp} placeholder="Nombre completo" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                <input type="email" autoComplete="email" value={form.email} onChange={e => setForm(f => ({ ...f, email: e.target.value }))} className={inp} placeholder="email@ejemplo.com" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input autoComplete="tel" value={form.phone} onChange={e => setForm(f => ({ ...f, phone: e.target.value }))} className={inp} placeholder="+54 9 11 ..." />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                <input autoComplete="organization-title" value={form.job_title} onChange={e => setForm(f => ({ ...f, job_title: e.target.value }))} className={inp} placeholder="Ej: Técnico Senior" />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Rol</label>
                <select value={form.role} onChange={e => setForm(f => ({ ...f, role: e.target.value }))} className={inp}>
                  <option value="">Seleccionar rol...</option>
                  {availableRoles.map(r => <option key={r.id} value={r.name}>{r.name}</option>)}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  {editUser ? 'Nueva contraseña (dejar vacío para no cambiar)' : 'Contraseña *'}
                </label>
                <div className="relative">
                  <input type={showPassword ? "text" : "password"} autoComplete={editUser ? "current-password" : "new-password"} value={form.password} onChange={e => setForm(f => ({ ...f, password: e.target.value }))} className={inp} placeholder="••••••••" />
                  <button type="button" onClick={() => setShowPassword(!showPassword)} className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                    {showPassword ? '🙈' : '👁️'}
                  </button>
                </div>
              </div>
              {error && <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{error}</div>}
              {success && <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{success}</div>}
              <button onClick={guardar} disabled={saving} className="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-4 rounded-xl disabled:opacity-50 transition-colors">
                {saving ? 'Guardando...' : editUser ? 'Guardar Cambios' : 'Crear Usuario'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
