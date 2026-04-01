-- Seed inicial de roles para SerTecApp
INSERT OR IGNORE INTO roles (id, name, created_at, updated_at) VALUES
  (1, 'administrador', datetime('now'), datetime('now')),
  (2, 'tecnico', datetime('now'), datetime('now'));
