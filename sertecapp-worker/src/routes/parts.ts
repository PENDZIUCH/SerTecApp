import { requireAuth, isResponse } from '../middleware/auth';
import { ok, err, paginate } from '../utils/response';
import { Env } from '../types';

export async function handleParts(request: Request, env: Env, path: string): Promise<Response> {
  const user = await requireAuth(request, env);
  if (isResponse(user)) return user;

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '15');
  const page = Math.max(1, parseInt(url.searchParams.get('page') || '1'));
  const offset = (page - 1) * perPage;
  const search = url.searchParams.get('search') || '';

  const idMatch = path.match(/\/api\/(?:parts|repuestos)\/(\d+)/);

  // GET /api/parts/:id
  if (idMatch && request.method === 'GET') {
    const row = await env.DB.prepare('SELECT * FROM parts WHERE id = ?').bind(idMatch[1]).first<any>();
    if (!row) return err('No encontrado', 404);
    return ok({ data: fmt(row) });
  }

  // PUT /api/parts/:id
  if (idMatch && request.method === 'PUT') {
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    if (!body.name) return err('name es requerido', 422);
    const now = new Date().toISOString();
    await env.DB.prepare(`UPDATE parts SET name=?,part_number=?,sku=?,description=?,unit_cost=?,stock_qty=?,min_stock_level=?,location=?,is_active=?,updated_at=? WHERE id=?`)
      .bind(body.name, body.part_number||null, body.sku||null, body.description||null,
        body.unit_cost||0, body.stock_qty||0, body.min_stock_level||0,
        body.location||null, body.is_active!==false?1:0, now, idMatch[1]).run();
    const updated = await env.DB.prepare('SELECT * FROM parts WHERE id = ?').bind(idMatch[1]).first<any>();
    return ok({ data: fmt(updated) });
  }

  // POST /api/parts
  if (request.method === 'POST' && !idMatch) {
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    if (!body.name) return err('name es requerido', 422);
    const now = new Date().toISOString();
    // Verificar si ya existe por part_number o sku para evitar duplicados en importación
    if (body.part_number) {
      const existing = await env.DB.prepare('SELECT id FROM parts WHERE part_number = ? AND is_active = 1').bind(body.part_number).first();
      if (existing) return err('Ya existe un repuesto con ese código', 409);
    }
    const result = await env.DB.prepare(`INSERT INTO parts (name,part_number,sku,description,unit_cost,stock_qty,min_stock_level,location,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,1,?,?)`)
      .bind(body.name, body.part_number||null, body.sku||null, body.description||null,
        body.unit_cost||0, body.stock_qty||0, body.min_stock_level||0,
        body.location||null, now, now).run();
    const newRow = await env.DB.prepare('SELECT * FROM parts WHERE id = ?').bind(result.meta.last_row_id).first<any>();
    return ok({ data: fmt(newRow) }, 201);
  }

  // GET /api/parts
  let where = 'WHERE is_active = 1';
  const params: any[] = [];
  if (search) {
    where += ' AND (name LIKE ? OR part_number LIKE ? OR sku LIKE ?)';
    params.push('%'+search+'%', '%'+search+'%', '%'+search+'%');
  }
  const total = await env.DB.prepare('SELECT COUNT(*) as n FROM parts ' + where).bind(...params).first<{n:number}>();
  const rows = await env.DB.prepare('SELECT * FROM parts ' + where + ' ORDER BY name LIMIT ? OFFSET ?').bind(...params, perPage, offset).all();
  return ok(paginate(rows.results.map(fmt), total?.n || 0, page, perPage));
}

function fmt(p: any) {
  return {
    id: p.id, part_number: p.part_number, name: p.name,
    sku: p.sku, description: p.description,
    unit_cost: p.unit_cost, stock_qty: p.stock_qty,
    min_stock_level: p.min_stock_level, location: p.location,
    fob_price_usd: p.fob_price_usd, markup_percent: p.markup_percent,
    sale_price_usd: p.sale_price_usd, equipment_model_id: p.equipment_model_id,
    is_active: !!p.is_active, created_at: p.created_at, updated_at: p.updated_at,
  };
}
