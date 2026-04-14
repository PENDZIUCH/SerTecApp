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
6. **MANTENER TODO DOCUMENTADO.** Crear archivos .md con cada fix, decisión, cambio.
7. **PASAR URLs COMO LINKS** — No como texto plano.

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

## URLs — Estado Actual (2026-04-14)

| Entorno | URL | Estado |
|---------|-----|--------|
| PWA Producción | [https://sertecapp-tecnicos.pages.dev](https://sertecapp-tecnicos.pages.dev) | ✅ Live |
| API Producción (Workers) | [https://sertecapp-worker.pendziuch.workers.dev](https://sertecapp-worker.pendziuch.workers.dev) | ✅ Live |
| Filament LOCAL | [http://localhost:8000/admin/login](http://localhost:8000/admin/login) | ✅ Funcional |
| Filament HOSTINGER | [https://demos.pendziuch.com/admin/login](https://demos.pendziuch.com/admin/login) | ✅ HTTP 200 OK (test manual pendiente) |
| PWA LOCAL | [http://localhost:3002](http://localhost:3002) | 🔄 Verificar |

---

## Credenciales

### Filament LOCAL (MySQL Laragon)
| Email | Password | Rol |
|-------|----------|-----|
| pendziuch@gmail.com | TBD | super_admin |
| admin@sertecapp.local | TBD | administrador |

### Filament HOSTINGER (MySQL Hostinger)
| Email | Password | Rol |
|-------|----------|-----|
| pendziuch@gmail.com | TBD | administrador |

**Nota:** Las passwords están hasheadas. Si no funcionan, resetear con:
```bash
php artisan tinker --execute="App\Models\User::where('email','X')->update(['password'=>bcrypt('nueva')]);"
```

---

## Ramas Git

| Rama | Propósito | Estado |
|------|-----------|--------|
| `main` | Producción estable | ✅ |
| `development` | Trabajo activo — Filament + MySQL | 🔄 Activo |

**REGLA:** Trabajar siempre en `development` para cambios de Filament/backend.

---

## Cómo Levantar Filament LOCAL

### 1. Verificar .env
```bash
# Debe apuntar a MySQL, NO SQLite
cp backend-laravel/.env.mysql.local backend-laravel/.env
grep DB_CONNECTION backend-laravel/.env  # Verificar que dice 'mysql'
```

### 2. Levantar Laravel
```bash
cd C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel
php artisan serve --host=127.0.0.1 --port=8000
```

Acceder a: [http://localhost:8000/admin/login](http://localhost:8000/admin/login)

### 3. Levantar PWA LOCAL
```bash
cd C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos
# Verificar que .env.local tiene:
# NEXT_PUBLIC_API_URL=http://localhost:8000

npm run dev  # → http://localhost:3002
```

---

## Estado de la DB MySQL LOCAL (sertecapp)

| Tabla | Registros | Fuente | Estado |
|-------|-----------|--------|--------|
| users | 5 | SQLite → MySQL | ✅ |
| customers | 311 | SQLite → MySQL | ✅ |
| parts | 363 | SQLite → MySQL | ✅ |
| work_orders | 22 | SQLite → MySQL | ✅ |
| roles | 7 | SQLite → MySQL + super_admin | ✅ |
| permissions | 50 | SQLite → MySQL | ✅ |

**Respaldo original:** `backend-laravel/database/database.sqlite`

---

## 🔧 FIX 2026-04-14 — Hostinger Login 403

**Problema:** Claude Code creó dos middlewares con errores de sintaxis:
- `app/Http/Middleware/FixClientIp.php:10`
- `app/Http/Middleware/TrustProxiedRequests.php:10`

**Solución aplicada:**
1. ✅ Eliminar archivos rotos vía SSH
2. ✅ `php artisan config:cache && php artisan cache:clear`
3. ✅ Agregar config SESSION/CSRF faltante al `.env`

**Resultado:** [https://demos.pendziuch.com/admin/login](https://demos.pendziuch.com/admin/login) → **HTTP 200 OK**

**Documentación:** Ver [ARCHIVOS/HOSTINGER_FIX_2026-04-14.md](ARCHIVOS/HOSTINGER_FIX_2026-04-14.md)

---

## Estado Actual (2026-04-14 17:10 UTC)

### ✅ Completado
- PWA + Cloudflare Workers + D1 en producción
- Filament local con MySQL — 14 resources, datos migrados
- Filament en Hostinger desplegado y accesible
- Login page carga correctamente en Hostinger (HTTP 200 OK)
- Cookies CSRF y session se setean correctamente
- Middlewares rotos eliminados

### 🔄 En Progreso
- **TEST MANUAL:** Intentar login con credentials en [https://demos.pendziuch.com/admin/login](https://demos.pendziuch.com/admin/login)
- Levantar servidores locales (Laravel 8000 + Next.js 3002)
- Verificar que PWA local conecta a API local

### ⏳ Pendiente
- Migrar datos (311 clientes, 363 repuestos) a MySQL Hostinger
- Testear flujo completo end-to-end
- Verificar que todas las apps están online y conectadas
- Merge `development` → `main`

---

## Próximos Pasos (2026-04-14)

1. ✅ Hostinger login responde HTTP 200 — **Aguardando test manual de POST**
2. Levantar Laravel en [http://localhost:8000](http://localhost:8000)
3. Levantar Next.js PWA en [http://localhost:3002](http://localhost:3002)
4. Verificar conexión: PWA → API Local
5. Testear login POST en Hostinger
6. Migrar datos a MySQL Hostinger
7. Testear flujo completo online

---

## Reglas Técnicas

- **SSH a Hostinger:** `ssh -i ~/.ssh/hostinger_sertecapp -p 65002 u283281385@147.79.103.125`
- **Laragon:** MySQL en 127.0.0.1:3306, user `root`, sin password
- **Git commits:** Siempre hacer commit después de cambios documentados
- **Documentación:** Crear `.md` para cada fix/decisión importante
- **URLs:** Siempre pasar como markdown links `[texto](url)`, nunca como texto plano

---

**Última actualización:** 2026-04-14 17:10 UTC  
**Estado:** En progreso — Hostinger login responde, apps locales por levantar