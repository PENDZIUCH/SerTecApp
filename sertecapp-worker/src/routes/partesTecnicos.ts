import { requireAuth, isResponse } from '../middleware/auth';
import { ok, err } from '../utils/response';
import { addLog } from '../utils/logger';
import { Env } from '../types';

export async function handlePartesTecnicos(request: Request, env: Env, path: string): Promise<Response> {
  const user = await requireAuth(request, env);
  if (isResponse(user)) return user;

  // GET /api/partes/:work_order_id
  const idMatch = path.match(/\/api\/partes\/(\d+)/);
  if (idMatch && request.method === 'GET') {
    const parte = await env.DB.prepare(`
      SELECT p.*, u.name as tecnico_name
      FROM work_order_partes p
      LEFT JOIN users u ON p.tecnico_id = u.id
      WHERE p.work_order_id = ?
      ORDER BY p.created_at DESC LIMIT 1
    `).bind(idMatch[1]).first<any>();
    if (!parte) return ok({ success: true, data: null });
    const repuestos = await env.DB.prepare('SELECT * FROM parte_repuestos WHERE parte_id = ?').bind(parte.id).all();
    return ok({ success: true, data: fmtParte(parte, repuestos.results) });
  }

  // POST /api/partes
  if (request.method === 'POST') {
    let body: any;
    try { body = await request.json(); } catch { return err('JSON invalido', 400); }

    const workOrderId = body.work_order_id || body.orden_id;
    if (!workOrderId) return err('orden_id o work_order_id es requerido', 422);

    const diagnostico = body.diagnostico || body.diagnosis || null;
    const trabajoRealizado = body.trabajo_realizado || body.work_done || null;
    const firmaBase64 = body.firma_base64 || body.signature || null;
    const tecnicoId = body.tecnico_id || body.technician_id || user.id;
    const repuestos = body.repuestos || body.repuestos_usados || [];

    // Geolocalización del técnico (si la manda el frontend)
    const lat = body.lat || body.latitude || null;
    const lng = body.lng || body.longitude || null;
    const geoAddress = body.geo_address || body.address || null;

    const now = new Date().toISOString();
    const fotosJson = body.fotos ? JSON.stringify(body.fotos) : null;

    const existing = await env.DB.prepare(
      'SELECT id FROM work_order_partes WHERE work_order_id = ?'
    ).bind(workOrderId).first<{id:number}>();

    let parteId: number;
    const isUpdate = !!existing;

    if (existing) {
      await env.DB.prepare(`
        UPDATE work_order_partes
        SET tecnico_id=?, diagnostico=?, trabajo_realizado=?, firma_base64=?, fotos=?, updated_at=?
        WHERE id=?
      `).bind(tecnicoId, diagnostico, trabajoRealizado, firmaBase64, fotosJson, now, existing.id).run();
      parteId = existing.id;
      await env.DB.prepare('DELETE FROM parte_repuestos WHERE parte_id = ?').bind(parteId).run();
    } else {
      const result = await env.DB.prepare(`
        INSERT INTO work_order_partes (work_order_id, tecnico_id, diagnostico, trabajo_realizado, firma_base64, fotos, synced, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
      `).bind(workOrderId, tecnicoId, diagnostico, trabajoRealizado, firmaBase64, fotosJson, now, now).run();
      parteId = result.meta.last_row_id as number;
      await env.DB.prepare('UPDATE work_orders SET status=?, completed_at=?, updated_at=? WHERE id=?')
        .bind('completado', now, now, workOrderId).run();
    }

    if (Array.isArray(repuestos) && repuestos.length > 0) {
      for (const rep of repuestos) {
        await env.DB.prepare('INSERT INTO parte_repuestos (parte_id, part_id, nombre, cantidad) VALUES (?,?,?,?)')
          .bind(parteId, rep.part_id||null, rep.nombre || rep.name || '', rep.cantidad || rep.quantity || 1).run();
      }
    }

    // Log con geolocalización
    const tecnico = await env.DB.prepare('SELECT name FROM users WHERE id = ?').bind(tecnicoId).first<any>();
    const logMsg = isUpdate
      ? `Parte actualizado por ${tecnico?.name || user.name}`
      : `Parte completado por ${tecnico?.name || user.name}`;

    await addLog(env, {
      work_order_id: workOrderId,
      log_type: isUpdate ? 'parte_actualizado' : 'parte_completado',
      message: logMsg,
      created_by: tecnicoId,
      user_name: tecnico?.name || user.name,
      metadata: {
        repuestos_count: repuestos.length,
        tiene_firma: !!firmaBase64,
        repuestos: repuestos.map((r: any) => ({ nombre: r.nombre || r.name, cantidad: r.cantidad || r.quantity || 1 })),
      },
      lat: lat ? parseFloat(lat) : undefined,
      lng: lng ? parseFloat(lng) : undefined,
      address: geoAddress,
    });

    const parte = await env.DB.prepare('SELECT * FROM work_order_partes WHERE id = ?').bind(parteId).first<any>();
    const reps = await env.DB.prepare('SELECT * FROM parte_repuestos WHERE parte_id = ?').bind(parteId).all();
    return ok({ success: true, message: 'Parte guardado exitosamente', data: { id: parteId, ...fmtParte(parte, reps.results) } }, isUpdate ? 200 : 201);
  }

  return err('Metodo no permitido', 405);
}

function fmtParte(p: any, repuestos: any[]) {
  return {
    id: p.id, work_order_id: p.work_order_id,
    tecnico_id: p.tecnico_id, tecnico_name: p.tecnico_name,
    diagnostico: p.diagnostico, trabajo_realizado: p.trabajo_realizado,
    diagnosis: p.diagnostico, work_done: p.trabajo_realizado,
    firma_base64: p.firma_base64, signature: p.firma_base64,
    fotos: p.fotos ? (() => { try { return JSON.parse(p.fotos); } catch { return []; } })() : [],
    repuestos: repuestos.map(r => ({ id: r.id, part_id: r.part_id, nombre: r.nombre, cantidad: r.cantidad })),
    synced: !!p.synced, created_at: p.created_at, updated_at: p.updated_at,
  };
}
