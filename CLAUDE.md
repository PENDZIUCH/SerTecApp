# SerTecApp — Contexto para Claude

> Este archivo se carga automáticamente al inicio de cada sesión. Mantenerlo actualizado es crítico.
> **Al final de cada sesión:** actualizar "Estado actual" y "Próximos pasos".

---

## Qué es este proyecto

App de gestión de órdenes de trabajo para técnicos de servicio técnico.
- **PWA móvil** para que técnicos vean y completen sus órdenes en campo
- **Admin panel (Filament)** para administración completa — el panel "real" de gestión
- Dueño/dev: Hugo Pendziuch (`pendziuch@gmail.com`)

---

## Stack

| Capa | Tecnología | Directorio |
|------|-----------|------------|
| Frontend PWA | Next.js 14 | `sertecapp-tecnicos/` |
| API producción | Cloudflare Workers (TypeScript) | `sertecapp-worker/` |
| Admin panel | Laravel 11 + Filament 3.2 | `backend-laravel/` |
| DB producción | Cloudflare D1 (SQLite edge) | — |
| DB local/Filament | MySQL 8.4 vía Laragon | DB: `sertecapp` |

---

## URLs

| Entorno | URL |
|---------|-----|
| Frontend producción | https://sertecapp-tecnicos.pages.dev |
| API producción | https://sertecapp-worker.pendziuch.workers.dev |
| Filament admin local | http://localhost:8000/admin/login |

---

## Credenciales

### Producción (Cloudflare D1)
| Email | PIN | Rol |
|-------|-----|-----|
| admin@sertecapp.local | 1234 | administrador |
| pendziuch@gmail.com | 1234 | administrador |
| tech@demo.com | 1234 | técnico |

### Filament local (MySQL)
| Email | Password | Rol |
|-------|----------|-----|
| admin@sertecapp.local | (ver seeder) | admin |
| pendziuch@gmail.com | admin1234 | super_admin |
| tecnico@sertecapp.local | (ver seeder) | technician |
| supervisor@sertecapp.local | (ver seeder) | supervisor |

### Roles en MySQL (Spatie Permission)
- `admin`, `technician`, `supervisor`, `customer_viewer` — creados 2026-04-09
- `super_admin` — creado 2026-04-13, asignado a pendziuch@gmail.com

---

## Ramas git

| Rama | Propósito |
|------|-----------|
| `main` | Producción estable — Cloudflare |
| `development` | Trabajo activo — Filament + MySQL local |

**Siempre trabajar en `development`** para cambios de Filament/backend. No tocar `main` hasta tener todo testeado.

### Diferencias development vs main
- `.env.mysql.local` — config MySQL para Laragon (en development, eliminado de main)
- `RoleController.php` + ruta `GET /api/roles` — roles dinámicos (en development, no mergeado)

---

## Cómo levantar local

### Requisito: Laragon corriendo (MySQL en puerto 3306)

```bash
# 1. Cambiar .env a MySQL
cp backend-laravel/.env.mysql.local backend-laravel/.env

# 2. Levantar Laravel
cd backend-laravel && php artisan serve --port=8000

# 3. Filament en: http://localhost:8000/admin/login
```

### Worker + Frontend local
```bash
cd sertecapp-worker && npx wrangler dev --local --port 8787
cd sertecapp-tecnicos && npx next dev --port 3002
```

---

## Filament — Estado de recursos (14 resources)

Todos completos con CRUD: WorkOrder, Customer, Equipment, User, Part, Budget, Visit, Subscription, WorkshopItem, WorkPart, Notification, PdfTemplate, SystemSetting, SystemLog.

Ver detalle en `FEATURES_FILAMENT_VS_APP.md`.

---

## Estado actual (última actualización: 2026-04-13)

### Completado ✅
- PWA Next.js — órdenes, partes con firma, offline, SW
- Cloudflare Worker API — auth, users, customers, workOrders, parts
- D1 producción — 311 clientes, 394 repuestos, 5 usuarios, 6 órdenes
- Filament admin local — 14 resources completos con roles (Spatie Permission)
- MySQL local funcionando con 61 tablas, 2.97 MB
- Roles dinámicos desde Filament → app (`GET /api/roles` en branch development)
- Importación Excel clientes y repuestos

### En progreso 🔄
- **Deploy Filament a producción** — pendiente elegir hosting
- **Merge development → main** — pendiente validar admin local primero

### Pendiente ⏳
- Elegir hosting para Filament (Hostinger shared PHP / Railway / VPS)
- Configurar env vars producción Filament
- Conectar app PWA a roles de Filament productivo
- Definir si D1 y MySQL se sincronizan o son independientes

---

## Próximos pasos (prioridad)

1. **Validar Filament local con pendziuch@gmail.com** — probar login, roles, permisos
2. **Decidir hosting Filament** — Hostinger shared PHP es la opción más barata y simple
3. **Deploy Filament** — configurar env, DB MySQL en nube, migraciones
4. **Merge development → main** — una vez validado en producción

---

## Reglas de trabajo (importante)

- Siempre dar **URLs clickeables**, no rutas de archivo
- Usar **Bash tool** para comandos artisan/npm (Desktop Commander no soporta `cd` con rutas con espacios)
- Al **inicio de sesión**: verificar que Laragon esté corriendo y `.env` apunte a MySQL
- Al **final de sesión**: actualizar este CLAUDE.md y generar informe en `docs/SESSION_REPORT.md`

---

## Workflow de sesión

**Al final de cada sesión con Claude Code o Claude Desktop:**
1. Pedir: *"Generá el informe de estado de esta sesión"*
2. Guardar el resultado en `docs/SESSION_REPORT.md`
3. Hacer commit: `git add CLAUDE.md docs/SESSION_REPORT.md && git commit -m "docs: actualizar estado sesión YYYY-MM-DD"`

Esto garantiza que la próxima sesión arranca con contexto completo.
