import { requireAuth, requireAdmin, isResponse } from '../middleware/auth';
import { ok, err, paginate } from '../utils/response';
import { Env } from '../types';
import * as bcrypt from 'bcryptjs';

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
      SELECT u.*, r.name as role_name FROM users u
      LEFT JOIN user_roles ur ON u.id = ur.user_id
      LEFT JOIN roles r ON ur.role_id = r.id
      WHERE u.id = ?
    `).bind(idMatch[1]).first<any>();
    if (!u) return err('No encontrado', 404);
    return ok({ data: fmt(u) });
  }

  // PUT /api/users/:id — editar usuario
  if (idMatch && request.method === 'PUT') {
    const admin = await requireAdmin(request, env);
    if (isResponse(admin)) return admin;
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    const now = new Date().toISOString();
    const isActive = body.is_active !== undefined ? (body.is_active ? 1 : 0) : 1;

    if (body.password) {
      const hash = bcrypt.hashSync(body.password, 10);
      await env.DB.prepare(`UPDATE users SET name=?, email=?, phone=?, job_title=?, password=?, is_active=?, updated_at=? WHERE id=?`)
        .bind(body.name, body.email, body.phone||null, body.job_title||null, hash, isActive, now, idMatch[1]).run();
    } else {
      await env.DB.prepare(`UPDATE users SET name=?, email=?, phone=?, job_title=?, is_active=?, updated_at=? WHERE id=?`)
        .bind(body.name, body.email, body.phone||null, body.job_title||null, isActive, now, idMatch[1]).run();
    }

    // Actualizar rol si viene
    if (body.role) {
      const role = await env.DB.prepare('SELECT id FROM roles WHERE name = ?').bind(body.role).first<{id:number}>();
      if (role) {
        await env.DB.prepare('DELETE FROM user_roles WHERE user_id = ?').bind(idMatch[1]).run();
        await env.DB.prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)').bind(idMatch[1], role.id).run();
      }
    }

    const updated = await env.DB.prepare(`
      SELECT u.*, r.name as role_name FROM users u
      LEFT JOIN user_roles ur ON u.id = ur.user_id
      LEFT JOIN roles r ON ur.role_id = r.id WHERE u.id = ?
    `).bind(idMatch[1]).first<any>();
    return ok({ data: fmt(updated) });
  }

  // POST /api/users — crear usuario
  if (request.method === 'POST' && !idMatch) {
    const admin = await requireAdmin(request, env);
    if (isResponse(admin)) return admin;
    let body: any; try { body = await request.json(); } catch { return err('JSON invalido'); }
    if (!body.name || !body.email || !body.password) return err('name, email y password son requeridos', 422);

    // Verificar email único
    const existing = await env.DB.prepare('SELECT id FROM users WHERE email = ?').bind(body.email).first();
    if (existing) return err('El email ya está registrado', 422);

    const hash = bcrypt.hashSync(body.password, 10);
    const now = new Date().toISOString();
    const result = await env.DB.prepare(`
      INSERT INTO users (name, email, password, phone, job_title, is_active, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, 1, ?, ?)
    `).bind(body.name, body.email, hash, body.phone||null, body.job_title||null, now, now).run();

    const newId = result.meta.last_row_id as number;

    // Asignar rol
    const roleName = body.role || 'tecnico';
    const role = await env.DB.prepare('SELECT id FROM roles WHERE name = ?').bind(roleName).first<{id:number}>();
    if (role) {
      await env.DB.prepare('INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)').bind(newId, role.id).run();
    }

    const newUser = await env.DB.prepare(`
      SELECT u.*, r.name as role_name FROM users u
      LEFT JOIN user_roles ur ON u.id = ur.user_id
      LEFT JOIN roles r ON ur.role_id = r.id WHERE u.id = ?
    `).bind(newId).first<any>();
    return ok({ data: fmt(newUser) }, 201);
  }

  // GET /api/users — lista todos (incluye inactivos para gestión)
  const showAll = url.searchParams.get('all') === '1';
  const whereClause = showAll ? '' : 'WHERE u.is_active = 1';
  const total = await env.DB.prepare(`SELECT COUNT(*) as n FROM users u ${whereClause}`).first<{n:number}>();
  const rows = await env.DB.prepare(`
    SELECT u.*, r.name as role_name FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    ${whereClause} ORDER BY u.name LIMIT ? OFFSET ?
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
