'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { API_URL } from '../../lib/config';
import { useToast } from '../../hooks/useToast';
import { Toast } from '../components/ui/Toast';
import { ParteForm } from '../components/ParteForm';

// "Mi Agenda" - pantalla del tecnico sobre el motor de agenda/reservas
// generico (App\Models\Booking del backend). El endpoint /api/v1/bookings
// ya autofiltra al tecnico logueado a sus propias reservas (mismo criterio
// que /api/v1/ordenes/tecnico/{id}); administrador/supervisor/super_admin
// ven la agenda completa porque el backend no les aplica ese filtro.
interface BookingSubject {
  id: number;
  wo_number?: string;
  title?: string;
  description?: string;
  priority?: 'low' | 'medium' | 'high' | 'urgent';
  customer?: { business_name?: string | null; first_name?: string | null; last_name?: string | null; address?: string | null; city?: string | null } | null;
  equipment?: { brand?: string | null; model?: string | null } | null;
  [key: string]: unknown;
}

interface Booking {
  id: number;
  resource_type: string;
  resource_id: number;
  subject_type: string | null;
  subject_id: number | null;
  subject?: BookingSubject | null;
  starts_at: string;
  ends_at: string | null;
  status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled' | 'no_show';
  check_in: string | null;
  check_out: string | null;
  duration_minutes: number | null;
  notes: string | null;
}

function getGeoLocation(): Promise<{ lat: number; lng: number } | null> {
  return new Promise((resolve) => {
    if (!navigator.geolocation) { resolve(null); return; }
    navigator.geolocation.getCurrentPosition(
      (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
      () => resolve(null),
      { timeout: 8000, maximumAge: 0 }
    );
  });
}

const statusLabel: Record<Booking['status'], string> = {
  scheduled: 'Programada',
  in_progress: 'En progreso',
  completed: 'Completada',
  cancelled: 'Cancelada',
  no_show: 'No se presentó',
};

const statusColor: Record<Booking['status'], string> = {
  scheduled: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
  in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
  completed: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
  cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
  no_show: 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
};

// Mismo mapeo que TechnicianController::mapPriority() en el backend - la
// orden viaja con el valor crudo en inglés (low/medium/high/urgent).
const priorityConfig: Record<string, { border: string; badge: string; label: string }> = {
  urgent: { border: 'border-l-4 border-red-500', badge: 'bg-red-500 text-white', label: 'Urgente' },
  high:   { border: 'border-l-4 border-orange-500', badge: 'bg-orange-500 text-white', label: 'Alta' },
  medium: { border: 'border-l-4 border-yellow-500', badge: 'bg-yellow-500 text-white', label: 'Media' },
  low:    { border: 'border-l-4 border-gray-400', badge: 'bg-gray-500 text-white', label: 'Baja' },
};

function customerName(customer?: BookingSubject['customer']): string {
  if (!customer) return '';
  return customer.business_name || [customer.first_name, customer.last_name].filter(Boolean).join(' ') || '';
}

function formatTime(iso: string | null): string {
  if (!iso) return '--:--';
  return new Date(iso).toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
}

function isSameDay(iso: string, ref: Date): boolean {
  const d = new Date(iso);
  return d.getFullYear() === ref.getFullYear() && d.getMonth() === ref.getMonth() && d.getDate() === ref.getDate();
}

export default function AgendaPage() {
  const router = useRouter();
  const [user, setUser] = useState<any>(null);
  const [bookings, setBookings] = useState<Booking[]>([]);
  const [loading, setLoading] = useState(true);
  const [actingOn, setActingOn] = useState<number | null>(null);
  const [parteModalOrderId, setParteModalOrderId] = useState<string | null>(null);
  const { toasts, showToast, hideToast, updateToast } = useToast();

  const loadBookings = async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) return;

      const from = new Date();
      from.setHours(0, 0, 0, 0);
      const to = new Date(from);
      to.setDate(to.getDate() + 14);

      const params = new URLSearchParams({
        from: from.toISOString().slice(0, 19).replace('T', ' '),
        to: to.toISOString().slice(0, 19).replace('T', ' '),
        per_page: '100',
      });

      const response = await fetch(`${API_URL}/api/v1/bookings?${params.toString()}`, {
        headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
        cache: 'no-store',
      });

      if (response.ok) {
        const data = await response.json();
        setBookings(data.data || []);
      } else {
        showToast('No se pudo cargar la agenda', 'error');
      }
    } catch (error) {
      console.error('Error cargando agenda:', error);
      showToast('Sin conexión para cargar la agenda', 'error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const token = localStorage.getItem('token');
    const userData = localStorage.getItem('user');
    if (!token || !userData) {
      router.push('/');
      return;
    }
    setUser(JSON.parse(userData));
    loadBookings();
  }, [router]);

  const handleCheckIn = async (booking: Booking) => {
    setActingOn(booking.id);
    const toastId = showToast('📍 Registrando check-in...', 'loading');
    try {
      const token = localStorage.getItem('token');
      const geo = await getGeoLocation();

      const response = await fetch(`${API_URL}/api/v1/bookings/${booking.id}/check-in`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(geo ? { latitude: geo.lat, longitude: geo.lng } : {}),
      });

      if (response.ok) {
        updateToast(toastId, '✅ Check-in registrado', 'success');
        await loadBookings();
      } else {
        updateToast(toastId, '❌ No se pudo registrar el check-in', 'error');
      }
    } catch (error) {
      console.error('Error en check-in:', error);
      updateToast(toastId, '❌ Error de conexión', 'error');
    } finally {
      setActingOn(null);
    }
  };

  const handleCheckOut = async (booking: Booking) => {
    setActingOn(booking.id);
    const toastId = showToast('📍 Registrando check-out...', 'loading');
    try {
      const token = localStorage.getItem('token');
      const geo = await getGeoLocation();

      const response = await fetch(`${API_URL}/api/v1/bookings/${booking.id}/check-out`, {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(geo ? { latitude: geo.lat, longitude: geo.lng } : {}),
      });

      if (response.ok) {
        updateToast(toastId, '✅ Check-out registrado', 'success');
        await loadBookings();
      } else {
        updateToast(toastId, '❌ No se pudo registrar el check-out', 'error');
      }
    } catch (error) {
      console.error('Error en check-out:', error);
      updateToast(toastId, '❌ Error de conexión', 'error');
    } finally {
      setActingOn(null);
    }
  };

  if (!user) {
    return (
      <div className="min-h-screen bg-gray-100 dark:bg-gray-900 flex items-center justify-center">
        <div className="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  const today = new Date();
  const todayBookings = bookings.filter((b) => isSameDay(b.starts_at, today));
  const upcomingBookings = bookings
    .filter((b) => !isSameDay(b.starts_at, today))
    .sort((a, b) => new Date(a.starts_at).getTime() - new Date(b.starts_at).getTime());

  const renderBooking = (booking: Booking) => {
    const subject = booking.subject;
    const client = customerName(subject?.customer);
    const wo = subject?.wo_number ? `#${subject.wo_number}` : (booking.subject_id ? `#${booking.subject_id}` : '');
    const equipmentLabel = [subject?.equipment?.brand, subject?.equipment?.model].filter(Boolean).join(' ');
    const address = [subject?.customer?.address, subject?.customer?.city].filter(Boolean).join(', ');
    const prio = subject?.priority ? priorityConfig[subject.priority] : null;
    const busy = actingOn === booking.id;

    return (
      <div
        key={booking.id}
        className={`${prio?.border || 'border-l-4 border-transparent'} bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm`}
      >
        <div className="flex items-center gap-1.5 mb-2 flex-wrap">
          {prio && <span className={`${prio.badge} text-xs font-medium px-2.5 py-0.5 rounded-full`}>{prio.label}</span>}
          <span className={`text-xs font-medium px-2.5 py-0.5 rounded-full whitespace-nowrap ${statusColor[booking.status]}`}>
            {statusLabel[booking.status]}
          </span>
          <span className="ml-auto text-xs font-semibold text-gray-500 dark:text-gray-400">
            {formatTime(booking.starts_at)}{booking.ends_at ? ` – ${formatTime(booking.ends_at)}` : ''}
          </span>
        </div>

        <h3 className="text-base font-semibold text-gray-900 dark:text-white leading-tight">
          {client || 'Cliente sin nombre'}
        </h3>
        <p className="text-sm text-gray-600 dark:text-gray-300 mt-0.5">
          {subject?.title || subject?.description || 'Sin descripción'}
        </p>
        {equipmentLabel && (
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">🔧 {equipmentLabel}</p>
        )}
        {address && (
          <div className="flex items-center gap-1 mt-1.5 text-xs text-gray-400 dark:text-gray-500">
            <svg className="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <span>{address}</span>
          </div>
        )}
        {wo && <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">Orden {wo}</p>}

        {booking.notes && (
          <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">{booking.notes}</p>
        )}

        {booking.status === 'in_progress' && booking.check_in && (
          <p className="text-xs text-gray-400 dark:text-gray-500 mt-2 mb-2">
            Check-in: {formatTime(booking.check_in)}
          </p>
        )}

        {booking.status === 'in_progress' && booking.subject_type?.endsWith('WorkOrder') && booking.subject_id && (
          <button
            onClick={() => setParteModalOrderId(String(booking.subject_id))}
            className="w-full bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold py-2 rounded-lg transition-colors mb-2"
          >
            📝 Crear Parte
          </button>
        )}

        {(booking.status === 'scheduled' || booking.status === 'in_progress') && (
          <div className="flex gap-2 mt-2">
            {booking.status === 'scheduled' && (
              <button
                onClick={() => handleCheckIn(booking)}
                disabled={busy}
                className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-sm font-semibold py-2 rounded-lg transition-colors"
              >
                {busy ? 'Enviando...' : 'Check-in'}
              </button>
            )}
            {booking.status === 'in_progress' && (
              <button
                onClick={() => handleCheckOut(booking)}
                disabled={busy}
                className="flex-1 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white text-sm font-semibold py-2 rounded-lg transition-colors"
              >
                {busy ? 'Enviando...' : 'Check-out'}
              </button>
            )}
          </div>
        )}
      </div>
    );
  };

  return (
    <div className="min-h-screen bg-gray-100 dark:bg-gray-950">
      <header className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-10">
        <div className="max-w-3xl mx-auto px-4 py-3 flex items-center gap-3">
          <button
            onClick={() => router.push('/ordenes')}
            className="p-2 -ml-2 text-gray-500 dark:text-gray-400 hover:text-gray-800 dark:hover:text-white"
          >
            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div>
            <h1 className="text-base font-semibold text-gray-900 dark:text-white">Mi Agenda</h1>
            <p className="text-xs text-gray-500 dark:text-gray-400">{user.name || 'Técnico'}</p>
          </div>
        </div>
      </header>

      <div className="max-w-3xl mx-auto px-4 py-4 space-y-6">
        {loading ? (
          <div className="text-center py-12">
            <div className="animate-spin h-8 w-8 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
          </div>
        ) : (
          <>
            <section>
              <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                Hoy ({todayBookings.length})
              </h2>
              {todayBookings.length > 0 ? (
                <div className="space-y-3">{todayBookings.map(renderBooking)}</div>
              ) : (
                <div className="text-center py-8 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                  <p className="text-gray-400 text-sm">Sin reservas para hoy</p>
                </div>
              )}
            </section>

            <section>
              <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                Próximas ({upcomingBookings.length})
              </h2>
              {upcomingBookings.length > 0 ? (
                <div className="space-y-3">{upcomingBookings.map(renderBooking)}</div>
              ) : (
                <div className="text-center py-8 bg-white/50 dark:bg-gray-800/50 rounded-lg">
                  <p className="text-gray-400 text-sm">Sin próximas reservas</p>
                </div>
              )}
            </section>
          </>
        )}
      </div>

      <div className="fixed top-4 right-4 z-50 space-y-2">
        {toasts.map((toast) => (
          <Toast key={toast.id} message={toast.message} type={toast.type} onClose={() => hideToast(toast.id)} />
        ))}
      </div>

      {/* Modal del Parte - misma orden de la visita, mismo formulario que /ordenes */}
      {parteModalOrderId && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-end md:items-center justify-center p-0 md:p-4">
          <div className="bg-white dark:bg-gray-900 w-full md:max-w-2xl md:rounded-2xl shadow-2xl flex flex-col max-h-screen md:max-h-[90vh]">
            <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
              <h2 className="text-base font-semibold text-gray-900 dark:text-white">Parte de Trabajo — Orden #{parteModalOrderId.padStart(4, '0')}</h2>
              <button onClick={() => setParteModalOrderId(null)} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
            <div className="flex-1 overflow-y-auto">
              <ParteForm
                orderId={parteModalOrderId}
                onSuccess={() => { setParteModalOrderId(null); loadBookings(); }}
                onCancel={() => setParteModalOrderId(null)}
              />
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
