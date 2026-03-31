# PLAN COMPLETO — Migración a Cloudflare Workers + D1
**Objetivo:** Eliminar dependencia del túnel y Laravel. Todo en Cloudflare, 100% gratis, offline-first.

---

## ARQUITECTURA FINAL

```
sertecapp-tecnicos.pages.dev  (o pro.pendziuch.com)
              ↓
    Next.js PWA (Cloudflare Pages)
              ↓  fetch a API
    sertecapp-api.pendziuch.com
              ↓
    Cloudflare Worker (sertecapp-worker)
              ↓
    Cloudflare D1 (sertecapp-db) — SQLite serverless
```

**Offline:** El Service Worker cachea las órdenes del técnico. Si no hay red, lee del cache.
Los partes técnicos se guardan en localStorage y sincronizan al volver la conexión.

---

## DATOS A MIGRAR

| Tabla           | Registros | Notas                                      |
|-----------------|-----------|--------------------------------------------|
| users           | 5         | passwords bcrypt — se resetean             |
| roles           | 4         | administrador, técnico, cliente, supervisor |
| model_has_roles  | 5         | relación user→role                         |
| customers       | 311       | con address, city, state, country          |
| equipments      | 3         | con customer_id, brand_id, model_id        |
| equipment_brands| ?         | necesario para equipments                  |
| equipment_models| ?         | necesario para equipments                  |
| parts           | 394       | repuestos Life Fitness                     |
| work_orders     | 17        | con customer_id, assigned_tech_id          |
| wo_parts_used   | ?         | repuestos usados en órdenes               |
| work_order_logs | ?         | historial de órdenes                       |

---

## ENDPOINTS DEL WORKER

### Auth
| Método | Ruta        | Descripción                              |
|--------|-------------|------------------------------------------|
| POST   | /api/login  | email + password → JWT token + user+roles|
| GET    | /api/me     | token → user actual                      |

### Work Orders
| Método | Ruta                              | Descripción                    |
|--------|-----------------------------------|--------------------------------|
| GET    | /api/work-orders                  | lista paginada (per_page, page)|
| POST   | /api/work-orders                  | crear orden                    |
| GET    | /api/work-orders/:id              | detalle de una orden           |
| PUT    | /api/work-orders/:id              | editar orden                   |
| POST   | /api/work-orders/:id/change-status| cambiar estado                 |
| GET    | /api/ordenes/tecnico/:id          | órdenes asignadas a técnico    |

### Clientes
| Método | Ruta              | Descripción              |
|--------|-------------------|--------------------------|
| GET    | /api/customers    | lista paginada           |
| GET    | /api/customers/:id| detalle con equipos      |

### Equipos
| Método | Ruta              | Descripción                          |
|--------|-------------------|--------------------------------------|
| GET    | /api/equipments   | lista (filtrable por ?customer_id=X) |

### Usuarios
| Método | Ruta        | Descripción              |
|--------|-------------|--------------------------|
| GET    | /api/users  | lista con roles          |

### Repuestos
| Método | Ruta        | Descripción              |
|--------|-------------|--------------------------|
| GET    | /api/parts  | lista paginada           |

### Partes Técnicos
| Método | Ruta                 | Descripción                    |
|--------|----------------------|--------------------------------|
| POST   | /api/partes          | guardar parte técnico          |
| GET    | /api/partes/:orden_id| obtener parte de una orden     |

---

## SESIÓN 1 — Crear infraestructura y migrar datos

### Paso 1.1 — Crear proyecto Worker

```bash
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"
mkdir sertecapp-worker
cd sertecapp-worker
npm init -y
npm install -D wrangler
npx wrangler init --no-delegate-c3
```

### Paso 1.2 — Crear base D1

```bash
npx wrangler d1 create sertecapp-db
```

Guardar el output — tendrá el `database_id` que va en wrangler.toml:

```toml
# wrangler.toml
name = "sertecapp-worker"
main = "src/index.ts"
compatibility_date = "2024-01-01"

[[d1_databases]]
binding = "DB"
database_name = "sertecapp-db"
database_id = "PEGAR_EL_ID_QUE_DIO_EL_COMANDO"
```

### Paso 1.3 — Exportar SQLite actual

```bash
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"
sqlite3 database/database.sqlite .dump > ../sertecapp-worker/export_full.sql
```

### Paso 1.4 — Limpiar SQL para D1

D1 no soporta: PRAGMA foreign_keys, BEGIN TRANSACTION con ciertos tipos, algunos defaults.
Crear script de limpieza `clean_export.js`:

```javascript
const fs = require('fs');
let sql = fs.readFileSync('export_full.sql', 'utf8');

// Tablas que necesitamos (ignorar cache, sessions, jobs, etc de Laravel)
const tablesNeeded = [
  'roles', 'model_has_roles', 'users', 'customers',
  'equipment_brands', 'equipment_models', 'equipments',
  'parts', 'work_orders', 'wo_parts_used', 'work_order_logs'
];

// Filtrar solo las tablas necesarias
// El script extrae CREATE TABLE e INSERT INTO solo de esas tablas
// y elimina PRAGMAs incompatibles con D1

fs.writeFileSync('export_clean.sql', cleanedSql);
```

### Paso 1.5 — Crear schema D1

```sql
-- schema.sql — El schema simplificado para D1
CREATE TABLE IF NOT EXISTS roles (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  guard_name TEXT DEFAULT 'web',
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL,
  phone TEXT,
  job_title TEXT,
  is_active INTEGER DEFAULT 1,
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INTEGER,
  role_id INTEGER,
  PRIMARY KEY (user_id, role_id)
);

CREATE TABLE IF NOT EXISTS customers (
  id INTEGER PRIMARY KEY,
  customer_type TEXT DEFAULT 'individual',
  business_name TEXT,
  first_name TEXT,
  last_name TEXT,
  email TEXT,
  phone TEXT,
  tax_id TEXT,
  address TEXT,
  city TEXT,
  state TEXT,
  country TEXT,
  postal_code TEXT,
  is_active INTEGER DEFAULT 1,
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS equipment_brands (
  id INTEGER PRIMARY KEY,
  name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS equipment_models (
  id INTEGER PRIMARY KEY,
  brand_id INTEGER,
  name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS equipments (
  id INTEGER PRIMARY KEY,
  customer_id INTEGER,
  brand_id INTEGER,
  model_id INTEGER,
  serial_number TEXT,
  status TEXT DEFAULT 'active',
  location TEXT,
  notes TEXT,
  created_at TEXT,
  updated_at TEXT,
  FOREIGN KEY (customer_id) REFERENCES customers(id)
);

CREATE TABLE IF NOT EXISTS parts (
  id INTEGER PRIMARY KEY,
  part_number TEXT,
  name TEXT NOT NULL,
  sku TEXT,
  description TEXT,
  unit_cost REAL DEFAULT 0,
  stock_qty INTEGER DEFAULT 0,
  stock_quantity INTEGER DEFAULT 0,
  min_stock_level INTEGER DEFAULT 0,
  location TEXT,
  fob_price_usd REAL,
  markup_percent REAL,
  sale_price_usd REAL,
  is_active INTEGER DEFAULT 1,
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS work_orders (
  id INTEGER PRIMARY KEY,
  customer_id INTEGER,
  equipment_id INTEGER,
  wo_number TEXT,
  title TEXT NOT NULL,
  description TEXT,
  priority TEXT DEFAULT 'medium',
  status TEXT DEFAULT 'pendiente',
  assigned_tech_id INTEGER,
  scheduled_date TEXT,
  scheduled_time TEXT,
  started_at TEXT,
  completed_at TEXT,
  labor_cost REAL DEFAULT 0,
  parts_cost REAL DEFAULT 0,
  total_cost REAL DEFAULT 0,
  requires_signature INTEGER DEFAULT 0,
  created_at TEXT,
  updated_at TEXT,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (assigned_tech_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS work_order_logs (
  id INTEGER PRIMARY KEY,
  work_order_id INTEGER,
  log_type TEXT,
  message TEXT,
  created_by INTEGER,
  created_at TEXT,
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
);

CREATE TABLE IF NOT EXISTS wo_parts_used (
  id INTEGER PRIMARY KEY,
  work_order_id INTEGER,
  part_id INTEGER,
  quantity INTEGER DEFAULT 1,
  unit_cost REAL,
  total_cost REAL,
  created_at TEXT,
  updated_at TEXT
);

CREATE TABLE IF NOT EXISTS sessions (
  id TEXT PRIMARY KEY,
  user_id INTEGER,
  token TEXT NOT NULL UNIQUE,
  expires_at TEXT,
  created_at TEXT
);
```

### Paso 1.6 — Aplicar schema y datos a D1

```bash
npx wrangler d1 execute sertecapp-db --file=schema.sql
npx wrangler d1 execute sertecapp-db --file=export_clean.sql
```

### Paso 1.7 — Resetear passwords en D1

Los passwords de Laravel son bcrypt — el Worker usa bcryptjs.
Ejecutar SQL para resetear todos a '1234':

```bash
# El hash de '1234' con bcrypt rounds=10
npx wrangler d1 execute sertecapp-db --command="UPDATE users SET password='HASH_BCRYPT_1234'"
```

---

## SESIÓN 2 — Construir el Worker

### Estructura del proyecto

```
sertecapp-worker/
├── src/
│   ├── index.ts          — router principal
│   ├── auth.ts           — login, JWT, middleware
│   ├── routes/
│   │   ├── workOrders.ts
│   │   ├── customers.ts
│   │   ├── equipments.ts
│   │   ├── users.ts
│   │   ├── parts.ts
│   │   └── partes.ts
│   └── utils/
│       ├── jwt.ts        — sign/verify con Web Crypto API
│       ├── bcrypt.ts     — verificar passwords
│       └── paginate.ts   — helper paginación
├── wrangler.toml
└── package.json
```

### Dependencias

```json
{
  "dependencies": {
    "bcryptjs": "^2.4.3"
  },
  "devDependencies": {
    "wrangler": "^3.x",
    "@cloudflare/workers-types": "^4.x",
    "typescript": "^5.x"
  }
}
```

### src/index.ts — Router principal

```typescript
import { handleAuth } from './auth';
import { handleWorkOrders } from './routes/workOrders';
import { handleCustomers } from './routes/customers';
import { handleEquipments } from './routes/equipments';
import { handleUsers } from './routes/users';
import { handleParts } from './routes/parts';
import { handlePartes } from './routes/partes';

export interface Env {
  DB: D1Database;
  JWT_SECRET: string;
}

const CORS = {
  'Access-Control-Allow-Origin': '*',
  'Access-Control-Allow-Methods': 'GET, POST, PUT, DELETE, OPTIONS',
  'Access-Control-Allow-Headers': 'Content-Type, Authorization, Accept',
};

export default {
  async fetch(request: Request, env: Env): Promise<Response> {
    // CORS preflight
    if (request.method === 'OPTIONS') {
      return new Response(null, { headers: CORS });
    }

    const url = new URL(request.url);
    const path = url.pathname;

    try {
      let response: Response;

      if (path === '/api/login' && request.method === 'POST') {
        response = await handleAuth(request, env);
      } else if (path === '/api/me') {
        response = await handleMe(request, env);
      } else if (path.startsWith('/api/work-orders')) {
        response = await handleWorkOrders(request, env, path);
      } else if (path.startsWith('/api/customers')) {
        response = await handleCustomers(request, env, path);
      } else if (path.startsWith('/api/equipments')) {
        response = await handleEquipments(request, env, path);
      } else if (path.startsWith('/api/users')) {
        response = await handleUsers(request, env, path);
      } else if (path.startsWith('/api/parts')) {
        response = await handleParts(request, env, path);
      } else if (path.startsWith('/api/partes') || path.startsWith('/api/ordenes')) {
        response = await handlePartes(request, env, path);
      } else if (path === '/api/health') {
        response = new Response(JSON.stringify({ ok: true }), { status: 200 });
      } else {
        response = new Response(JSON.stringify({ message: 'Not found' }), { status: 404 });
      }

      // Agregar CORS a todas las respuestas
      const newHeaders = new Headers(response.headers);
      Object.entries(CORS).forEach(([k, v]) => newHeaders.set(k, v));
      return new Response(response.body, { status: response.status, headers: newHeaders });

    } catch (error: any) {
      return new Response(JSON.stringify({ message: error.message || 'Error interno' }), {
        status: 500,
        headers: { ...CORS, 'Content-Type': 'application/json' }
      });
    }
  }
};
```

### src/utils/jwt.ts — JWT con Web Crypto API (sin librerías)

```typescript
export async function signJWT(payload: object, secret: string): Promise<string> {
  const header = { alg: 'HS256', typ: 'JWT' };
  const enc = (obj: object) => btoa(JSON.stringify(obj)).replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_');
  const data = enc(header) + '.' + enc(payload);
  const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']);
  const sig = await crypto.subtle.sign('HMAC', key, new TextEncoder().encode(data));
  const sigB64 = btoa(String.fromCharCode(...new Uint8Array(sig))).replace(/=/g, '').replace(/\+/g, '-').replace(/\//g, '_');
  return data + '.' + sigB64;
}

export async function verifyJWT(token: string, secret: string): Promise<any | null> {
  try {
    const [h, p, s] = token.split('.');
    const data = h + '.' + p;
    const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(secret), { name: 'HMAC', hash: 'SHA-256' }, false, ['verify']);
    const sigBytes = Uint8Array.from(atob(s.replace(/-/g, '+').replace(/_/g, '/')), c => c.charCodeAt(0));
    const valid = await crypto.subtle.verify('HMAC', key, sigBytes, new TextEncoder().encode(data));
    if (!valid) return null;
    return JSON.parse(atob(p.replace(/-/g, '+').replace(/_/g, '/')));
  } catch { return null; }
}

export async function getUser(request: Request, env: { JWT_SECRET: string; DB: D1Database }) {
  const auth = request.headers.get('Authorization') || '';
  const token = auth.replace('Bearer ', '');
  if (!token) return null;
  const payload = await verifyJWT(token, env.JWT_SECRET);
  if (!payload) return null;
  const user = await env.DB.prepare('SELECT * FROM users WHERE id = ?').bind(payload.sub).first();
  if (!user) return null;
  const roleRow = await env.DB.prepare('SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?').bind(user.id).first<{name: string}>();
  return { ...user, roles: roleRow ? [roleRow.name] : [] };
}
```

### src/auth.ts — Login

```typescript
import * as bcrypt from 'bcryptjs';
import { signJWT } from './utils/jwt';
import { Env } from './index';

export async function handleAuth(request: Request, env: Env): Promise<Response> {
  const { email, password } = await request.json() as any;

  if (!email || !password) {
    return Response.json({ message: 'Email y contraseña requeridos' }, { status: 422 });
  }

  const user = await env.DB.prepare('SELECT * FROM users WHERE email = ? AND is_active = 1').bind(email).first<any>();

  if (!user || !bcrypt.compareSync(password, user.password)) {
    return Response.json({ errors: { email: ['Las credenciales ingresadas son incorrectas.'] } }, { status: 422 });
  }

  const roleRow = await env.DB.prepare(
    'SELECT r.name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?'
  ).bind(user.id).first<{name: string}>();

  const roles = roleRow ? [roleRow.name] : [];
  const token = await signJWT({ sub: user.id, email: user.email, exp: Math.floor(Date.now()/1000) + 86400*30 }, env.JWT_SECRET);

  return Response.json({
    token,
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
      job_title: user.job_title,
      is_active: user.is_active,
      roles,
    }
  });
}
```

### src/routes/workOrders.ts — Órdenes de trabajo

```typescript
import { getUser } from '../utils/jwt';
import { Env } from '../index';

const SELECT_ORDER = `
  SELECT wo.*,
    c.id as cust_id, c.business_name, c.first_name, c.email as cust_email, c.phone as cust_phone,
    c.address, c.city,
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

function formatOrder(row: any) {
  return {
    id: row.id,
    wo_number: row.wo_number,
    title: row.title,
    description: row.description,
    priority: row.priority,
    status: row.status,
    scheduled_date: row.scheduled_date,
    started_at: row.started_at,
    completed_at: row.completed_at,
    labor_cost: row.labor_cost,
    parts_cost: row.parts_cost,
    total_cost: row.total_cost,
    requires_signature: !!row.requires_signature,
    created_at: row.created_at,
    updated_at: row.updated_at,
    customer: row.cust_id ? {
      id: row.cust_id,
      business_name: row.business_name,
      full_name: [row.first_name].filter(Boolean).join(' '),
      email: row.cust_email,
      phone: row.cust_phone,
      full_address: [row.address, row.city].filter(Boolean).join(', '),
    } : null,
    assigned_tech: row.tech_id ? {
      id: row.tech_id,
      name: row.tech_name,
      email: row.tech_email,
    } : null,
    equipment: row.eq_id ? {
      id: row.eq_id,
      brand: row.brand_name,
      model: row.model_name,
      serial_number: row.serial_number,
    } : null,
  };
}

export async function handleWorkOrders(request: Request, env: Env, path: string): Promise<Response> {
  const user = await getUser(request, env);
  if (!user) return Response.json({ message: 'No autorizado' }, { status: 401 });

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '15');
  const page = parseInt(url.searchParams.get('page') || '1');
  const offset = (page - 1) * perPage;

  // GET /api/ordenes/tecnico/:id
  const techMatch = path.match(/\/api\/ordenes\/tecnico\/(\d+)/);
  if (techMatch) {
    const techId = techMatch[1];
    const rows = await env.DB.prepare(SELECT_ORDER + ' WHERE wo.assigned_tech_id = ? ORDER BY wo.created_at DESC').bind(techId).all();
    const orders = rows.results.map(formatOrder);
    return Response.json({ data: orders });
  }

  // GET /api/work-orders/:id
  const idMatch = path.match(/\/api\/work-orders\/(\d+)$/);
  if (idMatch && request.method === 'GET') {
    const row = await env.DB.prepare(SELECT_ORDER + ' WHERE wo.id = ?').bind(idMatch[1]).first();
    if (!row) return Response.json({ message: 'No encontrado' }, { status: 404 });
    return Response.json({ data: formatOrder(row) });
  }

  // POST /api/work-orders/:id/change-status
  const statusMatch = path.match(/\/api\/work-orders\/(\d+)\/change-status/);
  if (statusMatch && request.method === 'POST') {
    const { status } = await request.json() as any;
    const now = new Date().toISOString();
    await env.DB.prepare('UPDATE work_orders SET status = ?, updated_at = ? WHERE id = ?').bind(status, now, statusMatch[1]).run();
    const row = await env.DB.prepare(SELECT_ORDER + ' WHERE wo.id = ?').bind(statusMatch[1]).first();
    return Response.json({ data: formatOrder(row) });
  }

  // PUT /api/work-orders/:id
  if (idMatch && request.method === 'PUT') {
    const body = await request.json() as any;
    const now = new Date().toISOString();
    await env.DB.prepare(`
      UPDATE work_orders SET
        customer_id = ?, equipment_id = ?, title = ?, description = ?,
        priority = ?, assigned_tech_id = ?, scheduled_date = ?,
        requires_signature = ?, updated_at = ?
      WHERE id = ?
    `).bind(
      body.customer_id, body.equipment_id || null, body.title, body.description || null,
      body.priority || 'medium', body.assigned_tech_id || null, body.scheduled_date || null,
      body.requires_signature ? 1 : 0, now, idMatch[1]
    ).run();
    const row = await env.DB.prepare(SELECT_ORDER + ' WHERE wo.id = ?').bind(idMatch[1]).first();
    return Response.json({ data: formatOrder(row) });
  }

  // GET /api/work-orders — lista
  if (path === '/api/work-orders' && request.method === 'GET') {
    const total = await env.DB.prepare('SELECT COUNT(*) as n FROM work_orders').first<{n: number}>();
    const rows = await env.DB.prepare(SELECT_ORDER + ' ORDER BY wo.created_at DESC LIMIT ? OFFSET ?').bind(perPage, offset).all();
    return Response.json({
      data: rows.results.map(formatOrder),
      meta: { total: total?.n || 0, per_page: perPage, current_page: page, last_page: Math.ceil((total?.n || 0) / perPage) }
    });
  }

  // POST /api/work-orders — crear
  if (path === '/api/work-orders' && request.method === 'POST') {
    const body = await request.json() as any;
    const now = new Date().toISOString();
    const woNumber = 'WO-' + now.substring(0,10).replace(/-/g,'') + '-' + String(Math.floor(Math.random()*9999)).padStart(4,'0');
    const result = await env.DB.prepare(`
      INSERT INTO work_orders (customer_id, equipment_id, wo_number, title, description, priority, status, assigned_tech_id, scheduled_date, requires_signature, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?, ?)
    `).bind(
      body.customer_id, body.equipment_id || null, woNumber, body.title, body.description || null,
      body.priority || 'medium', body.assigned_tech_id || null, body.scheduled_date || null,
      body.requires_signature ? 1 : 0, now, now
    ).run();
    const row = await env.DB.prepare(SELECT_ORDER + ' WHERE wo.id = ?').bind(result.meta.last_row_id).first();
    return Response.json({ data: formatOrder(row) }, { status: 201 });
  }

  return Response.json({ message: 'Not found' }, { status: 404 });
}
```

### src/routes/customers.ts

```typescript
import { getUser } from '../utils/jwt';
import { Env } from '../index';

export async function handleCustomers(request: Request, env: Env, path: string): Promise<Response> {
  const user = await getUser(request, env);
  if (!user) return Response.json({ message: 'No autorizado' }, { status: 401 });

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '15');
  const page = parseInt(url.searchParams.get('page') || '1');
  const offset = (page - 1) * perPage;
  const search = url.searchParams.get('search') || '';

  const idMatch = path.match(/\/api\/customers\/(\d+)/);
  if (idMatch) {
    const c = await env.DB.prepare('SELECT * FROM customers WHERE id = ?').bind(idMatch[1]).first<any>();
    if (!c) return Response.json({ message: 'No encontrado' }, { status: 404 });
    const equips = await env.DB.prepare(`
      SELECT e.*, eb.name as brand, em.name as model
      FROM equipments e
      LEFT JOIN equipment_brands eb ON e.brand_id = eb.id
      LEFT JOIN equipment_models em ON e.model_id = em.id
      WHERE e.customer_id = ?
    `).bind(c.id).all();
    return Response.json({ data: formatCustomer(c, equips.results) });
  }

  let query = 'SELECT * FROM customers WHERE 1=1';
  const params: any[] = [];
  if (search) { query += ' AND (business_name LIKE ? OR first_name LIKE ? OR email LIKE ?)'; params.push('%'+search+'%','%'+search+'%','%'+search+'%'); }

  const total = await env.DB.prepare(query.replace('SELECT *', 'SELECT COUNT(*) as n')).bind(...params).first<{n:number}>();
  const rows = await env.DB.prepare(query + ' ORDER BY business_name, first_name LIMIT ? OFFSET ?').bind(...params, perPage, offset).all();

  return Response.json({
    data: rows.results.map(c => formatCustomer(c as any)),
    meta: { total: total?.n || 0, per_page: perPage, current_page: page, last_page: Math.ceil((total?.n || 0) / perPage) }
  });
}

function formatCustomer(c: any, equipments?: any[]) {
  return {
    id: c.id,
    customer_type: c.customer_type,
    business_name: c.business_name,
    full_name: [c.first_name, c.last_name].filter(Boolean).join(' ') || c.business_name,
    first_name: c.first_name,
    last_name: c.last_name,
    email: c.email,
    phone: c.phone,
    tax_id: c.tax_id,
    full_address: [c.address, c.city, c.state, c.country].filter(Boolean).join(', '),
    is_active: !!c.is_active,
    contacts: [],
    addresses: [],
    equipments: equipments?.map(e => ({ id: e.id, brand: e.brand, model: e.model, serial_number: e.serial_number, customer_id: e.customer_id })) || undefined,
    created_at: c.created_at,
    updated_at: c.updated_at,
  };
}
```

### src/routes/equipments.ts

```typescript
import { getUser } from '../utils/jwt';
import { Env } from '../index';

export async function handleEquipments(request: Request, env: Env, path: string): Promise<Response> {
  const user = await getUser(request, env);
  if (!user) return Response.json({ message: 'No autorizado' }, { status: 401 });

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '15');
  const page = parseInt(url.searchParams.get('page') || '1');
  const offset = (page - 1) * perPage;
  const customerId = url.searchParams.get('customer_id');

  let query = `
    SELECT e.*, eb.name as brand, em.name as model
    FROM equipments e
    LEFT JOIN equipment_brands eb ON e.brand_id = eb.id
    LEFT JOIN equipment_models em ON e.model_id = em.id
    WHERE 1=1
  `;
  const params: any[] = [];
  if (customerId) { query += ' AND e.customer_id = ?'; params.push(customerId); }

  const total = await env.DB.prepare(query.replace('SELECT e.*, eb.name as brand, em.name as model', 'SELECT COUNT(*) as n')).bind(...params).first<{n:number}>();
  const rows = await env.DB.prepare(query + ' LIMIT ? OFFSET ?').bind(...params, perPage, offset).all();

  return Response.json({
    data: rows.results.map((e: any) => ({
      id: e.id,
      customer_id: e.customer_id,
      brand: e.brand,
      model: e.model,
      serial_number: e.serial_number,
      status: e.status,
      location: e.location,
    })),
    meta: { total: total?.n || 0, per_page: perPage, current_page: page }
  });
}
```

### src/routes/users.ts

```typescript
import { getUser } from '../utils/jwt';
import { Env } from '../index';

export async function handleUsers(request: Request, env: Env, path: string): Promise<Response> {
  const user = await getUser(request, env);
  if (!user) return Response.json({ message: 'No autorizado' }, { status: 401 });

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '100');
  const page = parseInt(url.searchParams.get('page') || '1');
  const offset = (page - 1) * perPage;

  const rows = await env.DB.prepare(`
    SELECT u.*, r.name as role_name
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_id = r.id
    WHERE u.is_active = 1
    LIMIT ? OFFSET ?
  `).bind(perPage, offset).all();

  const total = await env.DB.prepare('SELECT COUNT(*) as n FROM users WHERE is_active = 1').first<{n:number}>();

  return Response.json({
    data: rows.results.map((u: any) => ({
      id: u.id,
      name: u.name,
      email: u.email,
      phone: u.phone,
      job_title: u.job_title,
      is_active: !!u.is_active,
      roles: u.role_name ? [u.role_name] : [],
    })),
    meta: { total: total?.n || 0, per_page: perPage, current_page: page }
  });
}
```

### src/routes/parts.ts

```typescript
import { getUser } from '../utils/jwt';
import { Env } from '../index';

export async function handleParts(request: Request, env: Env, path: string): Promise<Response> {
  const user = await getUser(request, env);
  if (!user) return Response.json({ message: 'No autorizado' }, { status: 401 });

  const url = new URL(request.url);
  const perPage = parseInt(url.searchParams.get('per_page') || '15');
  const page = parseInt(url.searchParams.get('page') || '1');
  const offset = (page - 1) * perPage;
  const search = url.searchParams.get('search') || '';

  let query = 'SELECT * FROM parts WHERE is_active = 1';
  const params: any[] = [];
  if (search) { query += ' AND (name LIKE ? OR sku LIKE ?)'; params.push('%'+search+'%','%'+search+'%'); }

  const total = await env.DB.prepare(query.replace('SELECT *', 'SELECT COUNT(*) as n')).bind(...params).first<{n:number}>();
  const rows = await env.DB.prepare(query + ' ORDER BY name LIMIT ? OFFSET ?').bind(...params, perPage, offset).all();

  return Response.json({
    data: rows.results.map((p: any) => ({
      id: p.id,
      name: p.name,
      sku: p.sku,
      part_number: p.part_number,
      description: p.description,
      unit_cost: p.unit_cost,
      stock_quantity: p.stock_qty || p.stock_quantity,
      sale_price_usd: p.sale_price_usd,
      fob_price_usd: p.fob_price_usd,
      location: p.location,
    })),
    meta: { total: total?.n || 0, per_page: perPage, current_page: page }
  });
}
```

### src/routes/partes.ts

```typescript
import { getUser } from '../utils/jwt';
import { Env } from '../index';

export async function handlePartes(request: Request, env: Env, path: string): Promise<Response> {
  const user = await getUser(request, env);
  if (!user) return Response.json({ message: 'No autorizado' }, { status: 401 });

  // GET /api/partes/:orden_id
  const getMatch = path.match(/\/api\/partes\/(\d+)/);
  if (getMatch && request.method === 'GET') {
    const log = await env.DB.prepare(
      'SELECT * FROM work_order_logs WHERE work_order_id = ? AND log_type = "parte" ORDER BY created_at DESC LIMIT 1'
    ).bind(getMatch[1]).first<any>();
    if (!log) return Response.json({ success: false, data: null });
    let data: any = {};
    try { data = JSON.parse(log.message); } catch {}
    return Response.json({ success: true, data });
  }

  // POST /api/partes — guardar parte técnico
  if (request.method === 'POST') {
    const body = await request.json() as any;
    const now = new Date().toISOString();

    // Guardar como log en work_order_logs
    await env.DB.prepare(`
      INSERT INTO work_order_logs (work_order_id, log_type, message, created_by, created_at)
      VALUES (?, 'parte', ?, ?, ?)
    `).bind(
      body.orden_id,
      JSON.stringify({
        diagnostico: body.diagnostico,
        trabajo_realizado: body.trabajo_realizado,
        repuestos_usados: body.repuestos_usados,
        firma_base64: body.firma_base64,
        tecnico_id: body.tecnico_id,
      }),
      body.tecnico_id,
      now
    ).run();

    // Actualizar estado de la orden a completado
    await env.DB.prepare(
      'UPDATE work_orders SET status = ?, completed_at = ?, updated_at = ? WHERE id = ?'
    ).bind('completado', now, now, body.orden_id).run();

    return Response.json({ success: true, message: 'Parte guardado correctamente' }, { status: 201 });
  }

  return Response.json({ message: 'Not found' }, { status: 404 });
}
```

---

## SESIÓN 3 — Deploy del Worker y conectar frontend

### Paso 3.1 — Variables de entorno del Worker

```bash
npx wrangler secret put JWT_SECRET
# Ingresar: una cadena aleatoria larga (ej: openssl rand -hex 32)
```

### Paso 3.2 — Deploy del Worker

```bash
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-worker"
npx wrangler deploy
```

El Worker queda disponible en: `https://sertecapp-worker.TU_SUBDOMAIN.workers.dev`

### Paso 3.3 — Dominio personalizado (opcional)

En el dashboard de Cloudflare, agregar ruta personalizada:
`api.pendziuch.com` → Worker `sertecapp-worker`

### Paso 3.4 — Cambiar API_URL en el frontend

En `sertecapp-tecnicos`, cambiar la URL en todos los archivos:

```bash
# Buscar y reemplazar en todos los archivos:
# DE: https://sertecapp.pendziuch.com
# A:  https://sertecapp-worker.TU_SUBDOMAIN.workers.dev
# (o https://api.pendziuch.com si se configuró el dominio)
```

Archivos a cambiar:
- `app/page.tsx` (login)
- `app/ordenes/page.tsx`
- `app/parte/page.tsx`
- `app/detalle/page.tsx`
- `app/admin/page.tsx`
- `app/admin/orden/[id]/_client.tsx`
- `hooks/useOnlineStatus.ts` (URL del health check)
- `app/lib/storage.ts`

### Paso 3.5 — Deploy frontend

```bash
# Doble click en deploy.bat
# O manualmente:
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
set NEXT_EXPORT=1
npx next build --webpack
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
```

---

## SESIÓN 4 — Verificación y offline completo

### Checklist de pruebas

**Auth:**
- [ ] Login admin → va a /admin
- [ ] Login técnico → va a /ordenes
- [ ] Token inválido → redirige a login

**Admin:**
- [ ] Stats muestran números correctos
- [ ] Lista de órdenes carga
- [ ] Crear orden con cliente y técnico
- [ ] Editar orden — datos precargados correctamente
- [ ] Cambiar estado de orden

**Técnico:**
- [ ] Ve solo sus órdenes asignadas
- [ ] Crear parte con firma
- [ ] Parte guardado aparece en la orden

**Offline:**
- [ ] Órdenes cacheadas disponibles sin red
- [ ] Crear parte sin red → queda en localStorage
- [ ] Al volver la red → sincroniza automáticamente

### Dominio final

```
pro.pendziuch.com → sertecapp-tecnicos.pages.dev (Cloudflare Pages)
api.pendziuch.com → sertecapp-worker (Cloudflare Worker)
```

Cambio en Cloudflare DNS:
- `pro.pendziuch.com` CNAME → `sertecapp-tecnicos.pages.dev`
- `api.pendziuch.com` CNAME → Worker (se configura desde el dashboard)

---

## RESUMEN DE COSTOS

| Servicio             | Plan   | Costo    |
|---------------------|--------|----------|
| Cloudflare Pages    | Free   | $0/mes   |
| Cloudflare Workers  | Free   | $0/mes   |
| Cloudflare D1       | Free   | $0/mes   |
| dominio pendziuch.com | Ya pagado | $0 extra |
| **TOTAL**           |        | **$0**   |

Free tier de Workers: 100,000 requests/día. Más que suficiente para Luis.
Free tier de D1: 5GB storage, 25M rows leídas/día. Más que suficiente.

---

## NOTAS IMPORTANTES

1. **bcryptjs en Workers:** funciona perfectamente. Es pure JS, sin dependencias nativas.
2. **JWT:** se implementa con Web Crypto API nativa de Workers, sin librerías externas.
3. **D1 y SQLite:** D1 es SQLite compatible. El schema de Laravel migra sin problemas quitando columnas no esenciales.
4. **Offline:** La app actual ya tiene todo el código offline (storage.ts, useOnlineStatus.ts, SW). Solo cambia la URL de la API.
5. **Passwords:** Los bcrypt de Laravel ($2y$) son compatibles con bcryptjs ($2a$). Solo hay que cambiar el prefijo o resetear.
6. **CORS:** El Worker incluye headers CORS en todas las respuestas para permitir llamadas desde Pages.
