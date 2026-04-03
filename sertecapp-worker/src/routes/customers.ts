import { requireAuth, isResponse } from '../middleware/auth';
import { ok, err, paginate } from '../utils/response';
import { Env } from '../types';

function fmt(c: any, equipments?: any[]) {
  return {
    id: c.id, customer_type: c.customer_type,
    business_name: c.business_name,
    full_name: [c.first_name, c.last_name].filter(Boolean).join(' ') || c.business_name || '',
    first_name: c.first_name, last_name: c.last_name,
    email: c.email, phone: c.phone, tax_id: c.tax_id,
    full_address: [c.address, c.city, c.state, c.country].filter(Boolean).join(', '),
    is_active: !!c.is_active, contacts: [], addresses: [],
    ...(equipments !== undefined && { equipments }),
    created_at: c.created_at, updated_at: c.updated_at,
  };
}

export async function handleCustomers(request: Request, env: Env, path: string): Promise<Response> {
  const user = await requireAuth(request, env);
  if (isResponse(user)) return user;

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '15');
  const page = Math.max(1, parseInt(url.searchParams.get('page') || '1'));
  const offset = (page - 1) * perPage;
  const search = url.searchParams.get('search') || '';

  const idMatch = path.match(/\/api\/customers\/(\d+)/);

  // GET /api/customers/:id
  if (idMatch && request.method === 'GET') {
    const c = await env.DB.prepare('SELECT * FROM customers WHERE id = ?').bind(idMatch[1]).first<any>();
    if (!c) return err('No encontrado', 404);
    const equips = await env.DB.prepare(`SELECT e.*,eb.name as brand,em.name as model FROM equipments e LEFT JOIN equipment_brands eb ON e.brand_id=eb.id LEFT JOIN equipment_models em ON e.model_id=em.id WHERE e.customer_id=?`).bind(c.id).all();
    return ok({ data: fmt(c, equips.results.map((e:any) => ({ id:e.id, brand:e.brand, model:e.model, serial_number:e.serial_number, customer_id:e.customer_id }))) });
  }

  // PUT /api/customers/:id
  if (idMatch && request.method === 'PUT') {
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    const now = new Date().toISOString();
    await env.DB.prepare(`UPDATE customers SET customer_type=?,business_name=?,first_name=?,last_name=?,email=?,phone=?,tax_id=?,address=?,city=?,notes=?,updated_at=? WHERE id=?`)
      .bind(body.customer_type||'company', body.business_name||null, body.first_name||null, body.last_name||null,
        body.email||null, body.phone||null, body.tax_id||null, body.address||null, body.city||null,
        body.notes||null, now, idMatch[1]).run();
    const updated = await env.DB.prepare('SELECT * FROM customers WHERE id = ?').bind(idMatch[1]).first<any>();
    return ok({ data: fmt(updated) });
  }

  // POST /api/customers
  if (request.method === 'POST' && !idMatch) {
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    const nombre = body.business_name || body.first_name;
    if (!nombre) return err('Nombre o razón social requerido', 422);
    const now = new Date().toISOString();
    const result = await env.DB.prepare(`INSERT INTO customers (customer_type,business_name,first_name,last_name,email,phone,tax_id,address,city,notes,is_active,country,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,1,'Argentina',?,?)`)
      .bind(body.customer_type||'company', body.business_name||null, body.first_name||null, body.last_name||null,
        body.email||null, body.phone||null, body.tax_id||null, body.address||null, body.city||null,
        body.notes||null, now, now).run();
    const newId = result.meta.last_row_id as number;
    const newC = await env.DB.prepare('SELECT * FROM customers WHERE id = ?').bind(newId).first<any>();
    return ok({ data: fmt(newC) }, 201);
  }

  // GET /api/customers
  let where = 'WHERE is_active = 1';
  const params: any[] = [];
  if (search) { where += ' AND (business_name LIKE ? OR first_name LIKE ? OR email LIKE ? OR phone LIKE ?)'; params.push('%'+search+'%','%'+search+'%','%'+search+'%','%'+search+'%'); }
  const total = await env.DB.prepare('SELECT COUNT(*) as n FROM customers ' + where).bind(...params).first<{n:number}>();
  const rows = await env.DB.prepare('SELECT * FROM customers ' + where + ' ORDER BY business_name, first_name LIMIT ? OFFSET ?').bind(...params, perPage, offset).all();
  return ok(paginate(rows.results.map(c => fmt(c)), total?.n || 0, page, perPage));
}
