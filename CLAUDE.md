# SerTecApp — Contexto para Claude

> Cargado automáticamente al inicio de cada sesión.
> **Regla:** Al final de cada sesión actualizar "Estado actual" y "Próximos pasos" y hacer commit.

---

## Qué es este proyecto

Sistema de gestión de órdenes de trabajo para servicio técnico de equipos de fitness.
- **Cliente final:** Luis (Fitness Company — reparación de equipos fitness, CABA/GBA)
- **Desarrollador:** Hugo Pendziuch (`pendziuch@gmail.com`)
- **GitHub:** https://github.com/PENDZIUCH/SerTecApp
- **Dos interfaces:** Admin panel (Filament, uso interno) + PWA móvil (técnicos en campo)

---

## Stack

| Capa | Tecnología | Directorio |
|------|-----------|------------|
| Frontend PWA | Next.js 14 | `sertecapp-tecnicos/` |
| API producción | Cloudflare Workers (TypeScript) | `sertecapp-worker/` |
| Admin panel | Laravel 11 + Filament 3.2 + filament-shield | `backend-laravel/` |
| DB producción | Cloudflare D1 (SQLite edge) | — |
| DB local/Filament | MySQL 8.4 vía Laragon | DB: `sertecapp` |

---

## URLs

| Entorno | URL | Estado |
|---------|-----|--------|
| Frontend PWA producción | https://sertecapp-tecnicos.pages.dev | ✅ Live |
| API Worker producción | https://sertecapp-worker.pendziuch.workers.dev | ✅ Live |
| Filament admin local | http://localhost:8000/admin/login | local only |
| Hostinger demo (viejo) | https://demos.pendziuch.com/admin | ⚠️ verificar si sigue activo |

---

## Credenciales

### Producción (Cloudflare D1)
| Email | PIN | Rol |
|-------|-----|-----|
| admin@sertecapp.local | 1234 | administrador |
| pendziuch@gmail.com | 1234 | administrador |
| tech@demo.com | 1234 | técnico |

### Filament local (MySQL Laragon)
| Email | Password | Rol |
|-------|----------|-----|
| admin@sertecapp.local | (seeder) | admin |
| pendziuch@gmail.com | admin1234 | super_admin |
| tecnico@sertecapp.local | (seeder) | technician |
| supervisor@sertecapp.local | (seeder) | supervisor |

### Roles MySQL (Spatie Permission) — creados 2026-04-09
`admin`, `technician`, `supervisor`, `customer_viewer`, `super_admin`

---

## Ramas git

| Rama | Propósito |
|------|-----------|
| `main` | Producción estable — Cloudflare |
| `development` | Trabajo activo — Filament + MySQL local |

**Siempre trabajar en `development`** para cambios de Filament/backend.

### Lo que tiene `development` que `main` no tiene
- `.env.mysql.local` — config MySQL Laragon
- `RoleController.php` + ruta `GET /api/roles` — roles dinámicos desde Filament
- Roles en español (`técnico`, `supervisor`, etc.) alineados con D1

---

## Cómo levantar local

### Prerequisito: Laragon corriendo (MySQL puerto 3306, DB: sertecapp, user: root, sin password)

```bash
# Backend Filament
cp backend-laravel/.env.mysql.local backend-laravel/.env
cd backend-laravel && php artisan serve --port=8000
# → http://localhost:8000/admin/login

# PWA apuntando a local (rama development)
# sertecapp-tecnicos/.env.local tiene NEXT_PUBLIC_API_URL=http://localhost:8000
cd sertecapp-tecnicos && npm run dev
# → http://localhost:3000

# Worker local (si se necesita testear Cloudflare)
cd sertecapp-worker && npx wrangler dev --local --port 8787
```

---

## Filament Admin — Resources (14 completos)

| Resource | CRUD | Estado |
|----------|------|--------|
| WorkOrder | ✅ + cambiar estado + partes | Completo |
| Customer | ✅ + import/export Excel | Completo |
| Equipment | ✅ | Completo |
| User | ✅ + gestión roles/permisos | Completo |
| Part | ✅ + movimientos stock | Completo |
| Budget | ✅ + aprobar/rechazar | Completo |
| Visit | ✅ + check-in/out | Completo |
| Subscription | ✅ + renovar | Completo |
| WorkshopItem | ✅ inventario taller | Completo |
| WorkPart | ✅ partes usadas en órdenes | Completo |
| Notification | listar/ver | Completo |
| PdfTemplate | ✅ | Completo |
| SystemSetting | configuración global | Completo |
| SystemLog | auditoría | Completo |

---

## PWA App (`/admin`) — Estado de features vs Filament

Ver detalle completo en `FEATURES_FILAMENT_VS_APP.md`.

Resumen: WorkOrders (listar/crear ✅, editar/cambiar estado ❌), Customers (listar ✅, crear/editar ❌), Users (listar/crear ✅), Import Excel ✅.

---

## DB MySQL local — Estado

- 61 tablas, 2.97 MB
- Datos: clientes reales de Fitness Company (305+), repuestos Life Fitness, usuarios con roles
- Migración SQLite → MySQL completada (script: `MIGRATE_SQLITE_TO_MYSQL.ps1`)

---

## Decisiones arquitecturales importantes

1. **Cloudflare Workers + D1 para producción** — sin servidor PHP encendido, costo ~$0
2. **Filament separado** — necesita PHP hosting propio, no corre en Cloudflare
3. **MySQL para Filament** — SQLite era default pero se migró para producción real
4. **Roles dinámicos** — se crean en Filament, la app los consume via `GET /api/roles`
5. **Rama `development`** para no romper `main` mientras se prueba MySQL/Filament
6. **Demo en Hostinger** — se usó `demos.pendziuch.com/admin` para mostrar al cliente Luis

---

## Estado actual (última actualización: 2026-04-13)

### Completado ✅
- PWA Next.js — órdenes, partes con firma, offline, SW
- Cloudflare Worker API completa
- D1 producción — 311 clientes, 394 repuestos, 5 usuarios
- Filament admin local — 14 resources con roles Spatie
- MySQL local — 61 tablas, migración completa
- Import/Export Excel clientes y repuestos
- Roles dinámicos desde Filament → app

### En progreso 🔄
- Validar Filament local con pendziuch@gmail.com (sesión actual)
- Decidir hosting definitivo para Filament en producción
- Merge `development` → `main`

### Pendiente ⏳
- Elegir y ejecutar hosting Filament (Hostinger shared PHP / VPS / Railway)
- Configurar env vars producción Filament
- Conectar PWA a roles de Filament productivo
- Definir si D1 y MySQL se sincronizan o son independientes
- Features faltantes en PWA admin: editar orden, cambiar estado, detalle `/admin/orden`

---

## Próximos pasos (prioridad)

1. **Probar login Filament local** → http://localhost:8000/admin/login (`pendziuch@gmail.com` / `admin1234`)
2. **Decidir hosting Filament** — Hostinger ya fue usado antes para demo, es la opción más directa
3. **Deploy Filament producción** — configurar env, DB MySQL en nube, migraciones
4. **Merge `development` → `main`**
5. **Conectar PWA a roles Filament productivo**

---

## Reglas de trabajo

- Dar siempre **URLs clickeables**
- Usar **Bash tool** para comandos artisan/npm (Desktop Commander no soporta `cd` con rutas con espacios)
- Verificar al inicio: Laragon corriendo + `.env` apunta a MySQL
- **Al final de cada sesión:** actualizar este archivo + commit + generar informe si hubo cambios grandes

---

## Historial de sesiones (resúmenes en `ARCHIVOS/`)

- `ARCHIVOS/SESION_2024-12-09_RESUMEN.md` — inicio del proyecto
- `ARCHIVOS/RESUMEN_SESION_2025-12-11.md` — deploy Hostinger, import Excel, 305 clientes
- `ARCHIVOS/ESTADO_ACTUAL_30DIC2025.md` — estado completo a fines de 2025
- `ARCHIVOS/RESUMEN_06ENE2026.md` — online/offline, dark mode, magic link, roles español
- `sertecapp-tecnicos/SESION_DEPLOY.md` — migración a Cloudflare Workers+D1, bugs resueltos
