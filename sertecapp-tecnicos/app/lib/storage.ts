// Storage local para trabajar offline

export interface LocalParte {
  id: string; // UUID generado localmente
  orden_id: number;
  tecnico_id: number;
  diagnostico: string;
  trabajo_realizado: string;
  repuestos_usados: Array<{
    repuesto_id?: number;
    nombre: string;
    cantidad: number;
  }>;
  firma_base64?: string;
  fotos?: string[]; // Base64
  synced: boolean;
  created_at: string;
  updated_at: string;
}

const STORAGE_KEY = 'sertecapp_partes_pendientes';
const ORDENES_KEY = 'sertecapp_ordenes_cache';

// Guardar parte localmente (cuando no hay red)
export function saveParteLocal(parte: Omit<LocalParte, 'id' | 'created_at' | 'updated_at' | 'synced'>): LocalParte {
  const partes = getPartesLocal();
  
  const newParte: LocalParte = {
    ...parte,
    id: generateUUID(),
    synced: false,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
  };
  
  partes.push(newParte);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(partes));
  
  // NUEVO: Actualizar la orden en cache a completado
  const cachedOrders = getCachedOrdenes();
  if (cachedOrders) {
    const updatedOrders = cachedOrders.map((order: any) => 
      order.id === parte.orden_id 
        ? { ...order, status: 'completado' }
        : order
    );
    cacheOrdenes(updatedOrders);
  }
  
  return newParte;
}

// Obtener partes pendientes de sincronizar
export function getPartesLocal(): LocalParte[] {
  try {
    const data = localStorage.getItem(STORAGE_KEY);
    return data ? JSON.parse(data) : [];
  } catch (error) {
    console.error('Error leyendo partes locales:', error);
    return [];
  }
}

// Obtener partes no sincronizados
export function getPartesPendientesSync(): LocalParte[] {
  return getPartesLocal().filter(p => !p.synced);
}

// Marcar parte como sincronizado
export function markParteSynced(parteId: string, serverId?: number) {
  const partes = getPartesLocal();
  const updated = partes.map(p => 
    p.id === parteId 
      ? { ...p, synced: true, updated_at: new Date().toISOString() }
      : p
  );
  
  localStorage.setItem(STORAGE_KEY, JSON.stringify(updated));
}

// Limpiar partes sincronizados (opcional, después de confirmar)
export function clearSyncedPartes() {
  const partes = getPartesLocal().filter(p => !p.synced);
  localStorage.setItem(STORAGE_KEY, JSON.stringify(partes));
}

// Cachear órdenes para offline
export function cacheOrdenes(ordenes: any[]) {
  localStorage.setItem(ORDENES_KEY, JSON.stringify({
    data: ordenes,
    cached_at: new Date().toISOString()
  }));
}

// Obtener órdenes desde cache
export function getCachedOrdenes(): any[] | null {
  try {
    const data = localStorage.getItem(ORDENES_KEY);
    if (!data) return null;
    
    const parsed = JSON.parse(data);
    
    // Cache válido por 24 horas
    const cachedAt = new Date(parsed.cached_at);
    const now = new Date();
    const hoursDiff = (now.getTime() - cachedAt.getTime()) / (1000 * 60 * 60);
    
    if (hoursDiff > 24) {
      return null; // Cache expirado
    }
    
    return parsed.data;
  } catch (error) {
    console.error('Error leyendo cache de órdenes:', error);
    return null;
  }
}

// Detectar si hay conexión
export function isOnline(): boolean {
  return navigator.onLine;
}

// Sincronizar partes pendientes con el servidor
export async function syncPendingPartes(apiUrl: string, token: string): Promise<{ success: number; failed: number }> {
  const partes = getPartesPendientesSync();
  
  if (partes.length === 0) {
    return { success: 0, failed: 0 };
  }
  
  let success = 0;
  let failed = 0;
  
  for (const parte of partes) {
    try {
      const response = await fetch(`${apiUrl}/api/v1/partes`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
        body: JSON.stringify({
          orden_id: parte.orden_id,
          tecnico_id: parte.tecnico_id,
          diagnostico: parte.diagnostico,
          trabajo_realizado: parte.trabajo_realizado,
          repuestos_usados: parte.repuestos_usados,
          firma_base64: parte.firma_base64,
          fotos: parte.fotos,
        }),
      });
      
      if (response.ok) {
        const data = await response.json();
        markParteSynced(parte.id, data.data?.id);
        success++;
      } else {
        failed++;
      }
    } catch (error) {
      console.error('Error sincronizando parte:', error);
      failed++;
    }
  }
  
  return { success, failed };
}

// Generar UUID simple
function generateUUID(): string {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = Math.random() * 16 | 0;
    const v = c === 'x' ? r : (r & 0x3 | 0x8);
    return v.toString(16);
  });
}

// Listener de conexión
export function setupConnectionListener(onOnline: () => void, onOffline: () => void) {
  window.addEventListener('online', onOnline);
  window.addEventListener('offline', onOffline);
  
  return () => {
    window.removeEventListener('online', onOnline);
    window.removeEventListener('offline', onOffline);
  };
}
