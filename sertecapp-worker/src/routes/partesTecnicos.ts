import { requireAuth, isResponse } from '../middleware/auth';
import { ok, err } from '../utils/response';
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
      ORDER BY p.created_at DESC
      LIMIT 1
    `).bind(idMatch[1]).first<any>();

    if (!parte) return ok({ data: null });

    // Cargar repuestos usados
    const repuestos = await env.DB.prepare(
      'SELECT * FROM parte_repuestos WHERE parte_id = ?'
    ).bind(parte.id).all();

    return ok({ data: fmtParte(parte, repuestos.results) });
  }

  // POST /api/partes — crear/actualizar parte
  if (request.method === 'POST') {
    let body: any;
    try { body = await request.json(); } catch { return err('JSON invalido', 400); }

    if (!body.work_order_id) return err('work_order_id es requerido', 422);

    const now = new Date().toISOString();
    const fotosJson = body.fotos ? JSON.stringify(body.fotos) : null;

    // Verificar si ya existe parte para esta orden
    const existing = await env.DB.prepare(
      'SELECT id FROM work_order_partes WHERE work_order_id = ?'
    ).bind(body.work_order_id).first<{id:number}>();

    let parteId: number;

    if (existing) {
      // Actualizar
      await env.DB.prepare(`
        UPDATE work_order_partes
        SET tecnico_id=?, diagnostico=?, trabajo_realizado=?, firma_base64=?, fotos=?, updated_at=?
        WHERE id=?
      `).bind(body.tecnico_id||user.id, body.diagnostico||null, body.trabajo_realizado||null, body.firma_base64||null, fotosJson, now, existing.id).run();
      parteId = existing.id;

      // Limpiar repuestos anteriores y reinsertar
      await env.DB.prepare('DELETE FROM parte_repuestos WHERE parte_id = ?').bind(parteId).run();
    } else {
      // Crear nuevo
      const result = await env.DB.prepare(`
        INSERT INTO work_order_partes (work_order_id, tecnico_id, diagnostico, trabajo_realizado, firma_base64, fotos, synced, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
      `).bind(body.work_order_id, body.tecnico_id||user.id, body.diagnostico||null, body.trabajo_realizado||null, body.firma_base64||null, fotosJson, now, now).run();
      parteId = result.meta.last_row_id as number;

      // Actualizar estado de la orden a 'completado'
      await env.DB.prepare('UPDATE work_orders SET status=?, completed_at=?, updated_at=? WHERE id=?')
        .bind('completado', now, now, body.work_order_id).run();
    }

    // Insertar repuestos si vienen
    if (body.repuestos && Array.isArray(body.repuestos)) {
      for (const rep of body.repuestos) {
        await env.DB.prepare('INSERT INTO parte_repuestos (parte_id, part_id, nombre, cantidad) VALUES (?,?,?,?)')
          .bind(parteId, rep.part_id||null, rep.nombre||'', rep.cantidad||1).run();
      }
    }

    const parte = await env.DB.prepare('SELECT * FROM work_order_partes WHERE id = ?').bind(parteId).first<any>();
    const repuestos = await env.DB.prepare('SELECT * FROM parte_repuestos WHERE parte_id = ?').bind(parteId).all();
    return ok({ data: fmtParte(parte, repuestos.results) }, existing ? 200 : 201);
  }

  return err('Metodo no permitido', 405);
}

function fmtParte(p: any, repuestos: any[]) {
  return {
    id: p.id, work_order_id: p.work_order_id,
    tecnico_id: p.tecnico_id, tecnico_name: p.tecnico_name,
    diagnostico: p.diagnostico, trabajo_realizado: p.trabajo_realizado,
    firma_base64: p.firma_base64,
    fotos: p.fotos ? JSON.parse(p.fotos) : [],
    repuestos: repuestos.map(r => ({ id: r.id, part_id: r.part_id, nombre: r.nombre, cantidad: r.cantidad })),
    synced: !!p.synced, created_at: p.created_at, updated_at: p.updated_at,
  };
}
