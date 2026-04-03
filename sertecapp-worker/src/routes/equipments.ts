import { requireAuth, requireAdmin, isResponse } from '../middleware/auth';
import { ok, err, paginate } from '../utils/response';
import { Env } from '../types';

export async function handleEquipments(request: Request, env: Env, path: string): Promise<Response> {
  const user = await requireAuth(request, env);
  if (isResponse(user)) return user;

  const url = new URL(request.url);
  const customerId = url.searchParams.get('customer_id');

  const idMatch = path.match(/\/api\/equipments\/(\d+)/);

  // GET /api/equipments/:id
  if (idMatch && request.method === 'GET') {
    const row = await env.DB.prepare(`
      SELECT e.*, eb.name as brand_name, em.name as model_name
      FROM equipments e
      LEFT JOIN equipment_brands eb ON e.brand_id = eb.id
      LEFT JOIN equipment_models em ON e.model_id = em.id
      WHERE e.id = ?
    `).bind(idMatch[1]).first<any>();
    if (!row) return err('No encontrado', 404);
    return ok({ data: fmt(row) });
  }

  // GET /api/equipments?customer_id=X
  let where = 'WHERE 1=1';
  const params: any[] = [];
  if (customerId) { where += ' AND e.customer_id = ?'; params.push(customerId); }

  const rows = await env.DB.prepare(`
    SELECT e.*, eb.name as brand_name, em.name as model_name
    FROM equipments e
    LEFT JOIN equipment_brands eb ON e.brand_id = eb.id
    LEFT JOIN equipment_models em ON e.model_id = em.id
    ${where}
    ORDER BY eb.name, em.name
  `).bind(...params).all();

  return ok({ data: rows.results.map(fmt) });
}

function fmt(e: any) {
  return {
    id: e.id, customer_id: e.customer_id,
    serial_number: e.serial_number, equipment_code: e.equipment_code,
    location: e.location, status: e.status, notes: e.notes,
    purchase_date: e.purchase_date, warranty_expiration: e.warranty_expiration,
    next_service_date: e.next_service_date, last_service_date: e.last_service_date,
    brand: { id: e.brand_id, name: e.brand_name },
    model: { id: e.model_id, name: e.model_name },
    created_at: e.created_at, updated_at: e.updated_at,
  };
}
