-- SerTecApp D1 Schema — Sesion 1 Migracion
-- Cloudflare D1 (SQLite serverless)

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

CREATE TABLE IF NOT EXISTS work_orders (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  customer_id INTEGER NOT NULL,
  equipment_id INTEGER,
  wo_number TEXT UNIQUE,
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
  created_by INTEGER,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (equipment_id) REFERENCES equipments(id),
  FOREIGN KEY (assigned_tech_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS wo_parts_used (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  work_order_id INTEGER NOT NULL,
  part_id INTEGER,
  part_name TEXT NOT NULL,
  quantity INTEGER DEFAULT 1,
  unit_cost REAL DEFAULT 0,
  total_cost REAL DEFAULT 0,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (part_id) REFERENCES parts(id)
);

CREATE TABLE IF NOT EXISTS work_order_partes (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  work_order_id INTEGER NOT NULL,
  tecnico_id INTEGER,
  diagnostico TEXT,
  trabajo_realizado TEXT,
  firma_base64 TEXT,
  fotos TEXT,
  synced INTEGER DEFAULT 1,
  created_at TEXT DEFAULT (datetime('now')),
  updated_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
  FOREIGN KEY (tecnico_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS parte_repuestos (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  parte_id INTEGER NOT NULL,
  part_id INTEGER,
  nombre TEXT NOT NULL,
  cantidad INTEGER DEFAULT 1,
  FOREIGN KEY (parte_id) REFERENCES work_order_partes(id)
);

CREATE TABLE IF NOT EXISTS work_order_logs (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  work_order_id INTEGER NOT NULL,
  log_type TEXT,
  message TEXT,
  created_by INTEGER,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (work_order_id) REFERENCES work_orders(id)
);

CREATE TABLE IF NOT EXISTS sessions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  token_hash TEXT NOT NULL UNIQUE,
  expires_at TEXT,
  created_at TEXT DEFAULT (datetime('now')),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Indices de performance
CREATE INDEX IF NOT EXISTS idx_work_orders_customer ON work_orders(customer_id);
CREATE INDEX IF NOT EXISTS idx_work_orders_tech ON work_orders(assigned_tech_id);
CREATE INDEX IF NOT EXISTS idx_work_orders_status ON work_orders(status);
CREATE INDEX IF NOT EXISTS idx_equipments_customer ON equipments(customer_id);
CREATE INDEX IF NOT EXISTS idx_parts_active ON parts(is_active);
CREATE INDEX IF NOT EXISTS idx_partes_orden ON work_order_partes(work_order_id);
