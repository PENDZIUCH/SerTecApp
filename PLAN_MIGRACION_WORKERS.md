# PLAN ARQUITECTURA — SerTecApp v2
## Migración a Cloudflare Workers + D1 + Estrategia de Crecimiento
**Fecha:** 2026-03-31
**Versión:** 2.0

---

## PRINCIPIOS DE TRABAJO

1. **Nunca romper producción** — rama `main` siempre deployable
2. **Feature branches** — cada funcionalidad nueva en su propia rama
3. **Commit por cada cambio funcional** — mensajes descriptivos en español
4. **Probar local antes de mergear** — sin excepciones
5. **Código modular** — cada módulo independiente para facilitar integraciones futuras (Tango, etc.)

---

## ESTRUCTURA DE RAMAS GIT

```
main                    ← producción, siempre estable
  └── develop           ← integración de features
        ├── feature/workers-auth
        ├── feature/workers-work-orders
        ├── feature/workers-customers
        ├── feature/workers-parts
        ├── feature/workers-partes-tecnicos
        ├── feature/presupuestos
        ├── feature/tango-integration
        └── hotfix/*    ← fixes urgentes a main
```

### Reglas de merge
- `feature/*` → `develop` (via PR, con prueba local)
- `develop` → `main` (solo cuando todo funciona)
- `hotfix/*` → `main` + `develop`

---

## ARQUITECTURA FINAL

```
┌─────────────────────────────────────────────────────┐
│                   CLIENTE (PWA)                      │
│  sertecapp-tecnicos.pages.dev / pro.pendziuch.com   │
│                                                      │
│  ┌──────────────┐  ┌──────────────┐                 │
│  │  Vista Admin │  │ Vista Técnico│                 │
│  │  /admin      │  │  /ordenes    │                 │
│  └──────────────┘  └──────────────┘                 │
│         ↓                  ↓                         │
│    Service Worker (cache offline)                    │
└─────────────────────────────────────────────────────┘
              ↓ HTTPS
┌─────────────────────────────────────────────────────┐
│           CLOUDFLARE WORKER (API)                    │
│         api.pendziuch.com                           │
│                                                      │
│  ┌──────┐ ┌──────────┐ ┌──────────┐ ┌───────────┐  │
│  │ Auth │ │ Órdenes  │ │ Clientes │ │ Repuestos │  │
│  └──────┘ └──────────┘ └──────────┘ └───────────┘  │
│  ┌──────────────┐ ┌──────────────┐ ┌────────────┐  │
│  │ Presupuestos │ │    Partes    │ │   Tango    │  │
│  │  (futuro)    │ │  Técnicos    │ │  (futuro)  │  │
│  └──────────────┘ └──────────────┘ └────────────┘  │
└─────────────────────────────────────────────────────┘
              ↓
┌─────────────────────────────────────────────────────┐
│           CLOUDFLARE D1 (SQLite serverless)          │
│                sertecapp-db                          │
└─────────────────────────────────────────────────────┘
```

---

## ESTRUCTURA DEL PROYECTO WORKER

```
sertecapp-worker/
├── src/
│   ├── index.ts                 ← Router principal + CORS
│   ├── types.ts                 ← Interfaces TypeScript compartidas
│   ├── middleware/
│   │   └── auth.ts              ← JWT verify, getUser, requireAdmin
│   ├── utils/
│   │   ├── jwt.ts               ← sign/verify con Web Crypto API
│   │   ├── bcrypt.ts            ← verify passwords Laravel bcrypt
│   │   ├── paginate.ts          ← helper paginación estándar
│   │   ├── response.ts          ← helpers Response.json con CORS
│   │   └── validation.ts        ← validaciones comunes
│   ├── routes/
│   │   ├── auth.ts              ← POST /api/login, GET /api/me
│   │   ├── workOrders.ts        ← CRUD órdenes de trabajo
│   │   ├── customers.ts         ← CRUD clientes
│   │   ├── equipments.ts        ← CRUD equipos
│   │   ├── users.ts             ← CRUD usuarios
│   │   ├── parts.ts             ← CRUD repuestos
│   │   ├── partesTecnicos.ts    ← partes técnicos con firma
│   │   ├── budgets.ts           ← presupuestos (Fase 2)
│   │   └── tango.ts             ← integración Tango (Fase 3)
│   └── db/
│       ├── schema.sql           ← schema completo D1
│       ├── seed_roles.sql       ← datos iniciales roles
│       └── migrations/          ← migraciones futuras numeradas
│           ├── 001_initial.sql
│           ├── 002_budgets.sql
│           └── 003_tango.sql
├── scripts/
│   ├── export_sqlite.bat        ← exportar SQLite local
│   ├── clean_export.js          ← limpiar SQL para D1
│   └── reset_passwords.js       ← resetear passwords en D1
├── wrangler.toml
├── package.json
└── tsconfig.json
```

---

## BASE DE DATOS — Schema D1 Completo

```sql
-- migrations/001_initial.sql

-- ROLES Y PERMISOS
CREATE TABLE IF NOT EXISTS roles (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT NOT NULL UNIQUE,
  password TEXT NOT NULL,
  phone TEXT,
  job_title TEXT,
  is_active INTEGER DEFAULT 1,
  last_login_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INTEGER NOT NULL,
  role_id INTEGER NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- CLIENTES
CREATE TABLE IF NOT EXISTS customers (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_type TEXT DEFAULT 'individual',
  business_name TEXT,
  first_name TEXT,
  last_name TEXT,
  email TEXT,
  phone TEXT,
  secondary_email TEXT,
  tax_id TEXT,
  address TEXT,
  city TEXT,
  state TEXT,
  country TEXT DEFAULT 'Argentina',
  postal_code TEXT,
  is_active INTEGER DEFAULT 1,
  notes TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

-- EQUIPOS
CREATE TABLE IF NOT EXISTS equipment_brands (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS equipment_models (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  brand_id INTEGER,
  name TEXT NOT NULL,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (brand_id) REFERENCES equipment_brands(id)
);

CREATE TABLE IF NOT EXISTS equipments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER,
  brand_id INTEGER,
  model_id INTEGER,
  serial_number TEXT,
  equipment_code TEXT,
  purchase_date TEXT,
  warranty_expiration TEXT,
  next_service_date TEXT,
  last_service_date TEXT,
  location TEXT,
  status TEXT DEFAULT 'active',
  notes TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (brand_id) REFERENCES equipment_brands(id),
  FOREIGN KEY (model_id) REFERENCES equipment_models(id)
);

-- REPUESTOS
CREATE TABLE IF NOT EXISTS parts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  part_number TEXT,
  name TEXT NOT NULL,
  sku TEXT,
  description TEXT,
  unit_cost REAL DEFAULT 0,
  stock_qty INTEGER DEFAULT 0,
  min_stock_level INTEGER DEFAULT 0,
  location TEXT,
  fob_price_usd REAL,
  markup_percent REAL DEFAULT 0,
  sale_price_usd REAL,
  equipment_model_id INTEGER,
  is_active INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now'))
);

-- ÓRDENES DE TRABAJO
CREATE TABLE IF NOT EXISTS work_orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  equipment_id INTEGER,
  wo_number TEXT UNIQUE,
  title TEXT NOT NULL,
  description TEXT,
  priority TEXT DEFAULT 'medium',  -- low, medium, high, urgent
  status TEXT DEFAULT 'pendiente', -- pendiente, en_progreso, completado, cancelado
  assigned_tech_id INTEGER,
  scheduled_date TEXT,
  scheduled_time TEXT,
  started_at TEXT,
  completed_at TEXT,
  labor_cost REAL DEFAULT 0,
  parts_cost REAL DEFAULT 0,
  total_cost REAL DEFAULT 0,
  requires_signature INTEGER DEFAULT 0,
  created_by INTEGER,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (equipment_id) REFERENCES equipments(id),
  FOREIGN KEY (assigned_tech_id) REFERENCES users(id)
);

-- REPUESTOS USADOS EN ÓRDENES
CREATE TABLE IF NOT EXISTS wo_parts_used (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  work_order_id INTEGER NOT NULL,
  part_id INTEGER,
  part_name TEXT NOT NULL,  -- nombre en el momento del uso (por si cambia)
  quantity INTEGER DEFAULT 1,
  unit_cost REAL DEFAULT 0,
  total_cost REAL DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (part_id) REFERENCES parts(id)
);

-- PARTES TÉCNICOS (trabajo realizado)
CREATE TABLE IF NOT EXISTS work_order_partes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  work_order_id INTEGER NOT NULL,
  tecnico_id INTEGER,
  diagnostico TEXT,
  trabajo_realizado TEXT,
  firma_base64 TEXT,
  fotos TEXT,  -- JSON array de base64
  synced INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (tecnico_id) REFERENCES users(id)
);

-- REPUESTOS USADOS EN EL PARTE (relación con wo_parts_used)
CREATE TABLE IF NOT EXISTS parte_repuestos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  parte_id INTEGER NOT NULL,
  part_id INTEGER,
  nombre TEXT NOT NULL,
  cantidad INTEGER DEFAULT 1,
  FOREIGN KEY (parte_id) REFERENCES work_order_partes(id)
);

-- HISTORIAL DE ÓRDENES
CREATE TABLE IF NOT EXISTS work_order_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  work_order_id INTEGER NOT NULL,
  log_type TEXT,  -- status_change, note, parte, etc.
  message TEXT,
  created_by INTEGER,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
);

-- SESIONES/TOKENS
CREATE TABLE IF NOT EXISTS sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token_hash TEXT NOT NULL UNIQUE,
  expires_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ÍNDICES para performance
CREATE INDEX IF NOT EXISTS idx_work_orders_customer ON work_orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_work_orders_tech ON work_orders(assigned_tech_id);
CREATE INDEX IF NOT EXISTS idx_work_orders_status ON work_orders(status);
CREATE INDEX IF NOT EXISTS idx_equipments_customer ON equipments(customer_id);
CREATE INDEX IF NOT EXISTS idx_parts_active ON parts(is_active);
CREATE INDEX IF NOT EXISTS idx_partes_orden ON work_order_partes(work_order_id);
```

```sql
-- migrations/002_budgets.sql (Fase 2 — Presupuestos)

CREATE TABLE IF NOT EXISTS budgets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  budget_number TEXT UNIQUE,
  customer_id INTEGER NOT NULL,
  work_order_id INTEGER,
  title TEXT NOT NULL,
  description TEXT,
  status TEXT DEFAULT 'draft',  -- draft, sent, approved, rejected, expired
  subtotal REAL DEFAULT 0,
  tax_percent REAL DEFAULT 0,
  tax_amount REAL DEFAULT 0,
  total REAL DEFAULT 0,
  valid_until TEXT,
  notes TEXT,
  created_by INTEGER,
  approved_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
);

CREATE TABLE IF NOT EXISTS budget_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  budget_id INTEGER NOT NULL,
  part_id INTEGER,
  description TEXT NOT NULL,
  quantity INTEGER DEFAULT 1,
  unit_price REAL DEFAULT 0,
  total_price REAL DEFAULT 0,
  sort_order INTEGER DEFAULT 0,
  FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
  FOREIGN KEY (part_id) REFERENCES parts(id)
);
```

```sql
-- migrations/003_tango.sql (Fase 3 — Integración Tango)

CREATE TABLE IF NOT EXISTS tango_sync_log (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  entity_type TEXT NOT NULL,  -- customer, invoice, budget
  entity_id INTEGER NOT NULL,
  tango_id TEXT,
  status TEXT DEFAULT 'pending',  -- pending, synced, error
  error_message TEXT,
  synced_at TEXT,
  created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE IF NOT EXISTS invoices (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  invoice_number TEXT UNIQUE,
  customer_id INTEGER NOT NULL,
  work_order_id INTEGER,
  budget_id INTEGER,
  status TEXT DEFAULT 'draft',  -- draft, issued, paid, cancelled
  subtotal REAL DEFAULT 0,
  tax_percent REAL DEFAULT 21,   -- IVA Argentina
  tax_amount REAL DEFAULT 0,
  total REAL DEFAULT 0,
  tango_id TEXT,                 -- ID en Tango cuando se sincronice
  issued_at TEXT,
  due_at TEXT,
  paid_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (budget_id) REFERENCES budgets(id)
);
```

---

## PLAN DE SESIONES

### SESIÓN 1 — Setup y Migración de Datos (2-3 horas)

**Objetivo:** Infraestructura lista con todos los datos actuales en D1.

```
Paso 1: Crear rama
  git checkout -b feature/workers-setup

Paso 2: Inicializar proyecto Worker
  cd SerTecApp
  mkdir sertecapp-worker && cd sertecapp-worker
  npm init -y
  npm install -D wrangler typescript @cloudflare/workers-types
  npm install bcryptjs
  npm install -D @types/bcryptjs

Paso 3: Crear D1
  npx wrangler d1 create sertecapp-db
  → Guardar el database_id en wrangler.toml

Paso 4: Exportar SQLite
  sqlite3 ../backend-laravel/database/database.sqlite .dump > export_full.sql

Paso 5: Ejecutar script de limpieza clean_export.js
  → Genera export_clean.sql con solo las tablas necesarias
  → Reemplaza tipos incompatibles con D1
  → Convierte passwords $2y$ de Laravel a $2b$ compatible con bcryptjs

Paso 6: Aplicar schema
  npx wrangler d1 execute sertecapp-db --local --file=src/db/schema.sql
  npx wrangler d1 execute sertecapp-db --local --file=export_clean.sql

Paso 7: Verificar datos
  npx wrangler d1 execute sertecapp-db --local --command="SELECT COUNT(*) FROM customers"
  npx wrangler d1 execute sertecapp-db --local --command="SELECT COUNT(*) FROM parts"

Paso 8: Commit
  git add -A
  git commit -m "feat: setup worker + D1 con datos migrados"
```

### SESIÓN 2 — Worker API Core (3-4 horas)

**Objetivo:** Todos los endpoints funcionando en local.

```
Paso 1: Crear estructura de archivos src/
Paso 2: Implementar index.ts (router)
Paso 3: Implementar middleware/auth.ts
Paso 4: Implementar utils/ (jwt, bcrypt, response, paginate)
Paso 5: Implementar routes/auth.ts
Paso 6: Probar login local:
  npx wrangler dev --local
  curl -X POST http://localhost:8787/api/login -d '{"email":"admin@sertecapp.local","password":"1234"}'
Paso 7: Implementar routes/workOrders.ts
Paso 8: Implementar routes/customers.ts
Paso 9: Implementar routes/equipments.ts
Paso 10: Implementar routes/users.ts
Paso 11: Implementar routes/parts.ts
Paso 12: Implementar routes/partesTecnicos.ts
Paso 13: Probar todos los endpoints
Paso 14: Commit por cada ruta funcionando
  git commit -m "feat: auth endpoint funcionando"
  git commit -m "feat: work-orders CRUD completo"
  (etc.)
```

### SESIÓN 3 — Deploy Worker y Conectar Frontend (2 horas)

**Objetivo:** Frontend apuntando al Worker, todo funcionando en producción.

```
Paso 1: Deploy Worker a Cloudflare
  npx wrangler deploy
  → Nota la URL: https://sertecapp-worker.SUBDOMAIN.workers.dev

Paso 2: Configurar secret JWT
  npx wrangler secret put JWT_SECRET
  → Ingresar string aleatorio seguro

Paso 3: Deploy D1 en producción (no local)
  npx wrangler d1 execute sertecapp-db --file=src/db/schema.sql
  npx wrangler d1 execute sertecapp-db --file=export_clean.sql

Paso 4: Cambiar API_URL en frontend
  → Script node reemplaza todas las ocurrencias de sertecapp.pendziuch.com
  → Por la URL del Worker

Paso 5: Probar local con el Worker real
  npx next dev --port 3002
  → Login, órdenes, partes — todo debe funcionar

Paso 6: Crear rama para el frontend
  git checkout -b feature/connect-worker
  git commit -m "feat: frontend conectado a Cloudflare Worker"

Paso 7: Deploy frontend
  → deploy.bat

Paso 8: Merge a develop y luego a main
  git checkout develop
  git merge feature/workers-setup
  git merge feature/connect-worker
  git checkout main
  git merge develop
  git tag v2.0.0
```

### SESIÓN 4 — Verificación y Optimización (1-2 horas)

**Objetivo:** Todo funcionando en producción sin el túnel.

```
Checklist completo:
□ Login admin → /admin con datos reales
□ Login técnico → /ordenes con sus órdenes
□ Crear orden desde admin
□ Editar orden — datos precargados
□ Técnico crea parte con firma
□ Parte guardado offline → sincroniza al volver red
□ Stats del dashboard correctas (394 repuestos, 311 clientes)
□ Apuntar pro.pendziuch.com a Cloudflare Pages
□ Apuntar api.pendziuch.com al Worker
□ Commit final y tag v2.0.0-stable
```

### SESIÓN 5 — Presupuestos (3-4 horas)

**Objetivo:** Admin puede crear presupuestos con PDF.

```
Paso 1: git checkout -b feature/presupuestos
Paso 2: Aplicar migración 002_budgets.sql a D1
Paso 3: Implementar routes/budgets.ts en el Worker
Paso 4: Crear página /admin/presupuestos en el frontend
Paso 5: Crear página /admin/presupuesto/nuevo
Paso 6: Generación de PDF (usar @react-pdf/renderer o html2pdf)
Paso 7: Probar flujo completo
Paso 8: Merge a develop
```

### SESIÓN 6 — Integración Tango (planificación)

**Objetivo:** Sincronizar clientes y facturas con Tango Gestión.

```
Investigar:
□ ¿Qué versión de Tango usa Luis? (Tango Gestión, Astor, etc.)
□ ¿Tiene API o solo importación de archivos?
□ ¿Qué datos necesita Tango? (CUIT, razón social, comprobantes)
□ Formato de exportación (XML, TXT, JSON)

Opciones de integración:
A) API REST de Tango (si está disponible) → Worker llama directo
B) Archivo de exportación → el admin descarga y sube a Tango
C) Webhook → Tango llama al Worker cuando necesita datos

Implementar según lo que decida Luis.
Migración 003_tango.sql ya está diseñada para cualquier opción.
```

---

## REGLAS DE VERSIONADO

```
Formato: vMAJOR.MINOR.PATCH
  MAJOR: Cambio de arquitectura (v1→v2 = migración a Workers)
  MINOR: Nueva funcionalidad (presupuestos, Tango)
  PATCH: Fix o mejora menor

Tags importantes:
  v1.0.0 = Laravel + túnel (estado actual)
  v2.0.0 = Workers + D1 (migración)
  v2.1.0 = Presupuestos con PDF
  v2.2.0 = Integración Tango
  v3.0.0 = (futuro) App móvil nativa
```

---

## CONSIDERACIONES DE SEGURIDAD

```
1. JWT Secret: mínimo 32 caracteres aleatorios, guardado en Wrangler Secrets
2. Passwords: bcryptjs con 10 rounds (compatible con Laravel $2y$)
3. CORS: solo permitir orígenes de Cloudflare Pages
4. Rate limiting: Cloudflare lo maneja automáticamente en el free tier
5. D1: no expuesto públicamente, solo accesible desde el Worker
6. Tokens: expiración de 30 días, renovación automática
7. Admin endpoints: verificar rol 'administrador' en cada request
```

---

## CONSIDERACIONES PARA TANGO

```
Datos que SerTecApp puede exportar a Tango:
  - Clientes: razón social, CUIT, dirección, email, teléfono
  - Comprobantes: número, fecha, cliente, items, IVA, total
  - Artículos: código, descripción, precio

Campos críticos para Tango:
  - tax_id en customers → CUIT del cliente
  - invoice_number → número de comprobante
  - tax_percent → alícuota IVA (21% standard Argentina)

La tabla tango_sync_log permite rastrear qué se sincronizó
y reintentar en caso de error.
```

---

## COMANDOS ÚTILES

```bash
# Iniciar dev local del Worker
npx wrangler dev --local

# Ver logs del Worker en producción
npx wrangler tail

# Ejecutar query en D1 local
npx wrangler d1 execute sertecapp-db --local --command="SELECT * FROM users"

# Ejecutar query en D1 producción
npx wrangler d1 execute sertecapp-db --command="SELECT COUNT(*) FROM work_orders"

# Deploy Worker
npx wrangler deploy

# Deploy Frontend
cd ../sertecapp-tecnicos && deploy.bat

# Ver estado de la DB
npx wrangler d1 info sertecapp-db
```

---

## RESUMEN ECONÓMICO

| Servicio             | Límite Free      | Costo    |
|---------------------|-----------------|----------|
| Cloudflare Pages    | Deploys ilimitados | $0/mes |
| Cloudflare Workers  | 100k req/día    | $0/mes   |
| Cloudflare D1       | 5GB / 25M rows  | $0/mes   |
| dominio pendziuch.com | Ya pagado      | $0 extra |
| **TOTAL**           |                 | **$0**   |

Para referencia: con 10 técnicos haciendo 50 órdenes por día,
son ~500 requests/día. Muy por debajo del límite de 100k.
