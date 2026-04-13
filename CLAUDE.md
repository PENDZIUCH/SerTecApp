# SerTecApp — Contexto para Claude

> Este archivo se carga automáticamente. LEERLO COMPLETO antes de hacer cualquier cosa.
> Al final de cada sesión: actualizar estado y hacer commit.

---

## ⚠️ REGLAS CRÍTICAS — LEER PRIMERO

1. **NUNCA copiar/modificar `.env` sin preguntar al usuario primero.** Cambiar `.env` puede romper la DB en uso.
2. **NUNCA asumir que algo "está seguro" sin verificarlo.** Verificar siempre antes de afirmar.
3. **NUNCA crear usuarios/roles en DB sin preguntar.** El usuario sabe lo que tiene.
4. **Antes de cualquier acción destructiva o que cambie config: preguntar.**
5. **Si el usuario dice que algo estaba funcionando, creerle. No contradecirlo.**

---

## Qué es este proyecto

Sistema de gestión de órdenes de trabajo para servicio técnico de equipos de fitness.
- **Cliente final:** Luis (Fitness Company — reparación equipos fitness, CABA/GBA)
- **Desarrollador:** Hugo Pendziuch (`pendziuch@gmail.com`)
- **GitHub:** https://github.com/PENDZIUCH/SerTecApp
- **Dos interfaces:** Filament admin (uso interno) + PWA móvil (técnicos en campo)

---

## Stack

| Capa | Tecnología | Directorio |
|------|-----------|------------|
| Frontend PWA | Next.js 14 | `sertecapp-tecnicos/` |
| API producción | Cloudflare Workers (TypeScript) | `sertecapp-worker/` |
| Admin panel | Laravel 11 + Filament 3.2 + filament-shield | `backend-laravel/` |
| DB producción | Cloudflare D1 (SQLite edge) | — |
| DB local Filament | MySQL 8.4 vía Laragon | DB: `sertecapp` |

---

## URLs

| Entorno | URL | Estado |
|---------|-----|--------|
| Frontend PWA producción | https://sertecapp-tecnicos.pages.dev | ✅ Live |
| API Worker producción | https://sertecapp-worker.pendziuch.workers.dev | ✅ Live |
| Filament admin local | http://localhost:8000/admin/login | ✅ local |
| Filament admin Hostinger | https://demos.pendziuch.com/sertecapp/ | 🔄 Deploy 2026-04-13 |
| API Hostinger (futuro) | https://demos.pendziuch.com/sertecapp/api/ | ⏳ Próximo |

---

## Credenciales Filament local (MySQL)

| Email | Password | Rol |
|-------|----------|-----|
| pendziuch@gmail.com | ❓ pendiente confirmar | super_admin + administrador |
| admin@sertecapp.local | ❓ pendiente confirmar | administrador |
| luisgomez@fitnesscompany.com.ar | ❓ | customer_viewer |
| hcoronel@fitnesscompany.com.ar | ❓ | supervisor |
| tech@demo.com | ❓ | técnico |

> Las passwords están hasheadas en DB. Si no funcionan, resetear con:
> `php artisan tinker --execute="App\Models\User::where('email','X')->update(['password'=>bcrypt('nuevo')]);"` 

## Credenciales producción (Cloudflare D1)
| Email | PIN | Rol |
|-------|-----|-----|
| admin@sertecapp.local | 1234 | administrador |
| pendziuch@gmail.com | 1234 | administrador |
| tech@demo.com | 1234 | técnico |

---

## Ramas git

| Rama | Propósito |
|------|-----------|
| `main` | Producción estable |
| `development` | Trabajo activo — Filament + MySQL |

**Siempre trabajar en `development`** para cambios de Filament/backend.

---

## Cómo levantar Filament local

### IMPORTANTE: el `.env` DEBE apuntar a MySQL (no SQLite)
El archivo `.env.mysql.local` tiene la config correcta. Si `.env` dice `DB_CONNECTION=sqlite`, ejecutar:
```bash
cp backend-laravel/.env.mysql.local backend-laravel/.env
```
Verificar siempre con: `head -5 backend-laravel/.env | grep DB_CONNECTION`

### Pasos
1. Abrir Laragon (MySQL en puerto 3306)
2. `cp backend-laravel/.env.mysql.local backend-laravel/.env` (si no está ya)
3. `cd backend-laravel && php artisan serve --port=8000`
4. http://localhost:8000/admin/login

### PWA local (apunta a Laravel, no Cloudflare)
```bash
# sertecapp-tecnicos/.env.local debe tener:
# NEXT_PUBLIC_API_URL=http://localhost:8000
cd sertecapp-tecnicos && npm run dev  # → http://localhost:3000
```

---

## Estado de la DB MySQL (sertecapp) — 2026-04-13

| Tabla | Registros | Fuente |
|-------|-----------|--------|
| users | 5 | migrado de SQLite |
| customers | 311 | migrado de SQLite |
| parts | 363 | migrado de SQLite |
| work_orders | 22 | migrado de SQLite |
| roles | 7 | migrado + super_admin agregado |
| permissions | 50 | migrado de SQLite |

**Roles en MySQL:** administrador, técnico, supervisor, cliente, admin, customer_viewer, super_admin

**Script de migración:** `backend-laravel/migrate_sqlite_to_mysql.php`
> Si los datos de MySQL se pierden, ejecutar: `php migrate_sqlite_to_mysql.php`
> Los datos originales siempre están en: `backend-laravel/database/database.sqlite`

---

## Historia del problema de hoy (2026-04-13) — para no repetirlo

1. `.env` apuntaba a SQLite → Filament usaba SQLite con todos los datos
2. Copié `.env.mysql.local` a `.env` sin preguntar → Filament quedó apuntando a MySQL vacío
3. Los datos nunca habían sido migrados de SQLite a MySQL (el script anterior solo creaba esquema, no datos)
4. Tuvimos que migrar todo manualmente con `migrate_sqlite_to_mysql.php`
5. **Lección:** NUNCA cambiar `.env` sin preguntar primero

---

## Filament Admin — Resources (14 completos)

WorkOrder, Customer, Equipment, User, Part, Budget, Visit, Subscription,
WorkshopItem, WorkPart, Notification, PdfTemplate, SystemSetting, SystemLog

Ver detalle en `FEATURES_FILAMENT_VS_APP.md`.

---

## Rama development — diferencias vs main

- `.env.mysql.local` — config MySQL Laragon
- `RoleController.php` + ruta `GET /api/roles` — roles dinámicos desde Filament hacia la PWA
- Roles en español alineados con D1

---

## Estado actual (2026-04-13)

### Completado ✅
- PWA + Cloudflare Workers + D1 en producción
- Filament local con MySQL — 14 resources, datos migrados
- Roles/permisos con Spatie Permission (filament-shield)
- Import/Export Excel clientes y repuestos
- Migración SQLite → MySQL completada
- **Deploy Filament en Hostinger:** https://demos.pendziuch.com/sertecapp/
  - Rama `development` pusheada a Github
  - Clonada en Hostinger en SSH
  - Migraciones Laravel completadas
  - `.env` configurado con BD MySQL en Hostinger
  - Base de datos u283281385_sertecappers creada

### En progreso 🔄
- Migrar datos (311 clientes, 363 repuestos, etc.) desde SQLite local a MySQL Hostinger
- Testear acceso Filament en Hostinger
- Configurar endpoints API en `/sertecapp/api/`

### Pendiente ⏳
- Conectar PWA (sertecapp-tecnicos) a API de Hostinger
- Merge `development` → `main`
- Features PWA faltantes: editar orden, cambiar estado, detalle orden

---

## Próximos pasos

1. Confirmar que login Filament funciona → http://localhost:8000/admin/login
2. Elegir hosting para Filament
3. Deploy Filament a producción
4. Conectar PWA a roles de Filament productivo

---

## Reglas técnicas

- Usar **Bash tool** para comandos artisan/npm (Desktop Commander falla con rutas con espacios)
- Laragon: MySQL en 127.0.0.1:3306, user root, sin password
- `.env.sqlite.backup` existe como backup del `.env` SQLite original
- **Al final de sesión:** actualizar este archivo + `git add CLAUDE.md && git commit`
