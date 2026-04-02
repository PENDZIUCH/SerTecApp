import { Env } from '../types';

export type LogType =
  | 'orden_creada'
  | 'orden_editada'
  | 'estado_cambiado'
  | 'tecnico_asignado'
  | 'parte_completado'
  | 'parte_actualizado';

interface LogEntry {
  work_order_id: number;
  log_type: LogType;
  message: string;
  created_by?: number;
  user_name?: string;
  metadata?: Record<string, any>;
  lat?: number;
  lng?: number;
  address?: string;
}

export async function addLog(env: Env, entry: LogEntry): Promise<void> {
  try {
    const now = new Date().toISOString();
    await env.DB.prepare(`
      INSERT INTO work_order_logs
        (work_order_id, log_type, message, created_by, user_name, metadata, lat, lng, address, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `).bind(
      entry.work_order_id,
      entry.log_type,
      entry.message,
      entry.created_by || null,
      entry.user_name || null,
      entry.metadata ? JSON.stringify(entry.metadata) : null,
      entry.lat || null,
      entry.lng || null,
      entry.address || null,
      now,
    ).run();
  } catch (e) {
    // El log nunca debe romper la operación principal
    console.error('Error al guardar log:', e);
  }
}

export async function getLogs(env: Env, workOrderId: number): Promise<any[]> {
  const rows = await env.DB.prepare(`
    SELECT * FROM work_order_logs
    WHERE work_order_id = ?
    ORDER BY created_at ASC
  `).bind(workOrderId).all();

  return rows.results.map((r: any) => ({
    id: r.id,
    log_type: r.log_type,
    message: r.message,
    created_by: r.created_by,
    user_name: r.user_name,
    metadata: r.metadata ? (() => { try { return JSON.parse(r.metadata); } catch { return {}; } })() : {},
    lat: r.lat,
    lng: r.lng,
    address: r.address,
    created_at: r.created_at,
  }));
}
