import { Env } from './types';
import { cors, err } from './utils/response';
import { handleLogin, handleMe } from './routes/auth';
import { handleWorkOrders } from './routes/workOrders';
import { handleCustomers } from './routes/customers';
import { handleEquipments } from './routes/equipments';
import { handleUsers } from './routes/users';
import { handleParts } from './routes/parts';
import { handlePartesTecnicos } from './routes/partesTecnicos';

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    if (request.method === 'OPTIONS') return cors();

    const url = new URL(request.url);
    // Normalizar: acepta /api/v1/... y /api/... indistintamente
    const path = url.pathname.replace(/^\/api\/v1\//, '/api/');

    // Auth
    if (path === '/api/login' && request.method === 'POST') return handleLogin(request, env);
    if (path === '/api/me') return handleMe(request, env);

    // Work orders + ordenes técnico
    if (path.startsWith('/api/work-orders') || path.startsWith('/api/ordenes')) {
      return handleWorkOrders(request, env, path);
    }

    // Customers
    if (path.startsWith('/api/customers')) return handleCustomers(request, env, path);

    // Equipments
    if (path.startsWith('/api/equipments')) return handleEquipments(request, env, path);

    // Users / Técnicos
    if (path.startsWith('/api/users')) return handleUsers(request, env, path);

    // Parts / Repuestos
    if (path.startsWith('/api/parts') || path.startsWith('/api/repuestos')) {
      return handleParts(request, env, path);
    }

    // Partes técnicos
    if (path.startsWith('/api/partes')) return handlePartesTecnicos(request, env, path);

    // Health check
    if (path === '/api/health') {
      return new Response(JSON.stringify({ status: 'ok', ts: Date.now() }), {
        headers: { 'Content-Type': 'application/json' }
      });
    }

    return err('Ruta no encontrada', 404);
  },
} satisfies ExportedHandler<Env>;
