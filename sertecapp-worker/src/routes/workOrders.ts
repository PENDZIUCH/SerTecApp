import { requireAuth, isResponse } from '../middleware/auth';
import { ok, err, paginate } from '../utils/response';
import { Env } from '../types';

const SELECT = `
  SELECT wo.*,
    c.id as cust_id, c.business_name, c.first_name, c.last_name,
    c.email as cust_email, c.phone as cust_phone, c.address, c.city,
    u.id as tech_id, u.name as tech_name, u.email as tech_email,
    e.id as eq_id, e.serial_number,
    eb.name as brand_name, em.name as model_name
  FROM work_orders wo
  LEFT JOIN customers c ON wo.customer_id = c.id
  LEFT JOIN users u ON wo.assigned_tech_id = u.id
  LEFT JOIN equipments e ON wo.equipment_id = e.id
  LEFT JOIN equipment_brands eb ON e.brand_id = eb.id
  LEFT JOIN equipment_models em ON e.model_id = em.id
`;

// Formato admin — objeto customer anidado
function fmt(row: any) {
  return {
    id: row.id, wo_number: row.wo_number, title: row.title,
    description: row.description, priority: row.priority, status: row.status,
    scheduled_date: row.scheduled_date, started_at: row.started_at,
    completed_at: row.completed_at, labor_cost: row.labor_cost,
    parts_cost: row.parts_cost, total_cost: row.total_cost,
    requires_signature: !!row.requires_signature,
    created_at: row.created_at, updated_at: row.updated_at,
    customer: row.cust_id ? {
      id: row.cust_id,
      business_name: row.business_name,
      full_name: [row.first_name, row.last_name].filter(Boolean).join(' ') || row.business_name,
      email: row.cust_email, phone: row.cust_phone,
      full_address: [row.address, row.city].filter(Boolean).join(', '),
    } : null,
    assigned_tech: row.tech_id ? { id: row.tech_id, name: row.tech_name, email: row.tech_email } : null,
    equipment: row.eq_id ? { id: row.eq_id, brand: row.brand_name, model: row.model_name, serial_number: row.serial_number } : null,
  };
}

// Formato técnico — campos planos que espera ordenes/page.tsx
function fmtTecnico(row: any) {
  const clientName = row.business_name || [row.first_name, row.last_name].filter(Boolean).join(' ') || 'Sin cliente';
  const address = [row.address, row.city].filter(Boolean).join(', ') || '';
  const priority = ({ low:'baja', medium:'media', high:'alta', urgent:'urgente' } as any)[row.priority] || row.priority || 'media';
  const status = ({ completed:'completado', in_progress:'en_progreso', pending:'pendiente' } as any)[row.status] || row.status || 'pendiente';
  return {
    id: row.id,
    clientName,
    problem: row.description || row.title || '',
    address,
    priority,
    status,
    created_at: row.created_at,
    completed_at: row.completed_at,
    scheduled_date: row.scheduled_date,
    // También incluimos datos extra por si el frontend los necesita
    title: row.title,
    wo_number: row.wo_number,
    equipment: row.eq_id ? { brand: row.brand_name, model: row.model_name, serial: row.serial_number } : null,
    contact: { name: clientName, phone: row.cust_phone || '', email: row.cust_email || '' },
  };
}

export async function handleWorkOrders(request: Request, env: Env, path: string): Promise<Response> {
  const user = await requireAuth(request, env);
  if (isResponse(user)) return user;

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '15');
  const page = Math.max(1, parseInt(url.searchParams.get('page') || '1'));
  const offset = (page - 1) * perPage;

  // GET /api/ordenes/tecnico/:id — formato plano para la vista técnico
  const techMatch = path.match(/\/api\/ordenes\/tecnico\/(\d+)/);
  if (techMatch) {
    const rows = await env.DB.prepare(SELECT + 'WHERE wo.assigned_tech_id = ? ORDER BY wo.created_at DESC').bind(techMatch[1]).all();
    return ok({ success: true, data: rows.results.map(fmtTecnico) });
  }

  // POST /api/work-orders/:id/change-status
  const statusMatch = path.match(/\/api\/work-orders\/(\d+)\/change-status/);
  if (statusMatch && request.method === 'POST') {
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    const now = new Date().toISOString();
    await env.DB.prepare('UPDATE work_orders SET status = ?, updated_at = ? WHERE id = ?').bind(body.status, now, statusMatch[1]).run();
    const row = await env.DB.prepare(SELECT + 'WHERE wo.id = ?').bind(statusMatch[1]).first();
    if (!row) return err('No encontrado', 404);
    return ok({ data: fmt(row) });
  }

  const idMatch = path.match(/\/api\/work-orders\/(\d+)$/);

  // GET /api/work-orders/:id
  if (idMatch && request.method === 'GET') {
    const row = await env.DB.prepare(SELECT + 'WHERE wo.id = ?').bind(idMatch[1]).first();
    if (!row) return err('No encontrado', 404);
    return ok({ data: fmt(row) });
  }

  // PUT /api/work-orders/:id
  if (idMatch && request.method === 'PUT') {
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    if (!body.customer_id || !body.title) return err('customer_id y title son requeridos', 422);
    const now = new Date().toISOString();
    await env.DB.prepare(`UPDATE work_orders SET customer_id=?,equipment_id=?,title=?,description=?,priority=?,assigned_tech_id=?,scheduled_date=?,requires_signature=?,updated_at=? WHERE id=?`)
      .bind(body.customer_id, body.equipment_id||null, body.title, body.description||null, body.priority||'medium', body.assigned_tech_id||null, body.scheduled_date||null, body.requires_signature?1:0, now, idMatch[1]).run();
    const row = await env.DB.prepare(SELECT + 'WHERE wo.id = ?').bind(idMatch[1]).first();
    return ok({ data: fmt(row) });
  }

  // DELETE /api/work-orders/:id
  if (idMatch && request.method === 'DELETE') {
    await env.DB.prepare('DELETE FROM work_orders WHERE id = ?').bind(idMatch[1]).run();
    return new Response(null, { status: 204 });
  }

  // GET /api/work-orders
  if (request.method === 'GET') {
    const statusFilter = url.searchParams.get('status');
    const techFilter = url.searchParams.get('assigned_tech_id');
    let where = 'WHERE 1=1';
    const params: any[] = [];
    if (statusFilter) { where += ' AND wo.status = ?'; params.push(statusFilter); }
    if (techFilter) { where += ' AND wo.assigned_tech_id = ?'; params.push(techFilter); }
    const total = await env.DB.prepare('SELECT COUNT(*) as n FROM work_orders wo ' + where).bind(...params).first<{n:number}>();
    const rows = await env.DB.prepare(SELECT + where + ' ORDER BY wo.created_at DESC LIMIT ? OFFSET ?').bind(...params, perPage, offset).all();
    return ok(paginate(rows.results.map(fmt), total?.n || 0, page, perPage));
  }

  // POST /api/work-orders
  if (request.method === 'POST') {
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    if (!body.customer_id || !body.title) return err('customer_id y title son requeridos', 422);
    const now = new Date().toISOString();
    const woNum = 'WO-' + now.substring(0,10).replace(/-/g,'') + '-' + String(Math.floor(Math.random()*9999)).padStart(4,'0');
    const result = await env.DB.prepare(`INSERT INTO work_orders (customer_id,equipment_id,wo_number,title,description,priority,status,assigned_tech_id,scheduled_date,requires_signature,created_at,updated_at) VALUES (?,?,?,?,?,?,'pendiente',?,?,?,?,?)`)
      .bind(body.customer_id, body.equipment_id||null, woNum, body.title, body.description||null, body.priority||'medium', body.assigned_tech_id||null, body.scheduled_date||null, body.requires_signature?1:0, now, now).run();
    const row = await env.DB.prepare(SELECT + 'WHERE wo.id = ?').bind(result.meta.last_row_id).first();
    return ok({ data: fmt(row) }, 201);
  }

  return err('Metodo no permitido', 405);
}
