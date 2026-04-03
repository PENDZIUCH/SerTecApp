import * as bcrypt from 'bcryptjs';
import { signJWT } from '../utils/jwt';
import { ok, err } from '../utils/response';
import { Env } from '../types';

export async function handleLogin(request: Request, env: Env): Promise<Response> {
  let body: any;
  try { body = await request.json(); } catch { return err('JSON invalido', 400); }

  const { email, password } = body;
  if (!email || !password) return err('Email y contrasena requeridos', 422);

  const user = await env.DB.prepare(
    'SELECT u.*, r.name as role_name FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id WHERE u.email = ? AND u.is_active = 1'
  ).bind(email).first<any>();

  if (!user) return err('Credenciales incorrectas', 422, { email: ['Las credenciales ingresadas son incorrectas.'] });

  // Compatibilidad con bcrypt de Laravel ($2y$) y bcryptjs ($2b$)
  const hash = user.password.replace(/^\$2y\$/, '$2b$');
  const valid = bcrypt.compareSync(password, hash);
  if (!valid) return err('Credenciales incorrectas', 422, { email: ['Las credenciales ingresadas son incorrectas.'] });

  // Actualizar ultimo login
  await env.DB.prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
    .bind(new Date().toISOString(), user.id).run();

  const token = await signJWT(
    { sub: user.id, email: user.email, exp: Math.floor(Date.now() / 1000) + 86400 * 30 },
    env.JWT_SECRET
  );

  return ok({
    token,
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      job_title: user.job_title,
      is_active: !!user.is_active,
      roles: user.role_name ? [user.role_name] : [],
    }
  });
}

export async function handleMe(request: Request, env: Env): Promise<Response> {
  const { getUser } = await import('../middleware/auth');
  const user = await getUser(request, env);
  if (!user) return err('No autorizado', 401);
  return ok({ id: user.id, name: user.name, email: user.email, phone: user.phone, roles: user.roles });
}
