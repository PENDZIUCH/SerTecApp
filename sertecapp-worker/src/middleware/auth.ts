import { verifyJWT } from '../utils/jwt';
import { Env, User } from '../types';
import { err } from '../utils/response';

export async function getUser(request: Request, env: Env): Promise<User | null> {
  const auth = request.headers.get('Authorization') || '';
  const token = auth.replace('Bearer ', '').trim();
  if (!token) return null;

  const payload = await verifyJWT(token, env.JWT_SECRET);
  if (!payload || !payload.sub) return null;

  const user = await env.DB.prepare(
    'SELECT u.*, r.name as role_name FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id WHERE u.id = ? AND u.is_active = 1'
  ).bind(payload.sub).first<any>();

  if (!user) return null;
  return { ...user, roles: user.role_name ? [user.role_name] : [] };
}

export async function requireAuth(request: Request, env: Env): Promise<User | Response> {
  const user = await getUser(request, env);
  if (!user) return err('No autorizado', 401);
  return user;
}

export async function requireAdmin(request: Request, env: Env): Promise<User | Response> {
  const user = await getUser(request, env);
  if (!user) return err('No autorizado', 401);
  if (!user.roles.includes('administrador') && !user.roles.includes('admin')) {
    return err('Acceso denegado', 403);
  }
  return user;
}

export function isResponse(val: unknown): val is Response {
  return val instanceof Response;
}
