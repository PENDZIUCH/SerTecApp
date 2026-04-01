import { requireAuth, requireAdmin, isResponse } from '../middleware/auth';
import { ok, err, paginate } from '../utils/response';
import { Env } from '../types';

export async function handleUsers(request: Request, env: Env, path: string): Promise<Response> {
  const user = await requireAuth(request, env);
  if (isResponse(user)) return user;

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '50');
  const page = Math.max(1, parseInt(url.searchParams.get('page') || '1'));
  const offset = (page - 1) * perPage;

  const idMatch = path.match(/\/api\/users\/(\d+)/);

  // GET /api/users/:id
  if (idMatch && request.method === 'GET') {
    const u = await env.DB.prepare(`
      SELECT u.*, r.name as role_name
      FROM users u
      LEFT JOIN user_roles ur ON u.id = ur.user_id
      LEFT JOIN roles r ON ur.role_id = r.id
      WHERE u.id = ?
    `).bind(idMatch[1]).first<any>();
    if (!u) return err('No encontrado', 404);
    return ok({ data: fmt(u) });
  }

  // GET /api/users
  const total = await env.DB.prepare('SELECT COUNT(*) as n FROM users WHERE is_active = 1').first<{n:number}>();
  const rows = await env.DB.prepare(`
    SELECT u.*, r.name as role_name
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    WHERE u.is_active = 1
    ORDER BY u.name
    LIMIT ? OFFSET ?
  `).bind(perPage, offset).all();

  return ok(paginate(rows.results.map(fmt), total?.n || 0, page, perPage));
}

function fmt(u: any) {
  return {
    id: u.id, name: u.name, email: u.email,
    phone: u.phone, job_title: u.job_title,
    is_active: !!u.is_active, last_login_at: u.last_login_at,
    roles: u.role_name ? [u.role_name] : [],
    created_at: u.created_at, updated_at: u.updated_at,
  };
}
