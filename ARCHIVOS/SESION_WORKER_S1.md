# SESIÓN 1 WORKER — Estado al cierre
**Fecha:** 2026-04-01
**Estado:** ✅ CÓDIGO COMPLETO — ⏳ D1 + MIGRACIÓN DATOS PENDIENTE

---

## PROCESOS ONLINE AL CIERRE (confirmado por tasklist)

| Proceso | Estado |
|---------|--------|
| cloudflared.exe (x3) | ✅ CORRIENDO |
| php.exe (x6) | ✅ CORRIENDO (Laravel backend) |
| node.exe (x6) | ✅ CORRIENDO (Next.js frontend) |

**URLs activas:**
- Backend API: https://sertecapp.pendziuch.com (vía túnel)
- Frontend PWA: https://sertecapp-tecnicos.pages.dev (Cloudflare Pages)
- Frontend local: http://localhost:3002

---

## LO QUE SE HIZO EN ESTA SESIÓN

### ✅ Código del Worker 100% completo

| Archivo | Estado |
|---------|--------|
| `src/index.ts` | ✅ CREADO — router principal con todas las rutas |
| `src/types.ts` | ✅ ya existía |
| `src/utils/jwt.ts` | ✅ ya existía |
| `src/utils/response.ts` | ✅ ya existía |
| `src/middleware/auth.ts` | ✅ ya existía |
| `src/routes/auth.ts` | ✅ ya existía |
| `src/routes/workOrders.ts` | ✅ ya existía |
| `src/routes/customers.ts` | ✅ ya existía |
| `src/routes/equipments.ts` | ✅ CREADO |
| `src/routes/users.ts` | ✅ CREADO |
| `src/routes/parts.ts` | ✅ CREADO |
| `src/routes/partesTecnicos.ts` | ✅ CREADO |
| `src/db/schema.sql` | ✅ CREADO — schema D1 completo |
| `src/db/seed_roles.sql` | ✅ CREADO |

---

## LO QUE FALTA PARA TERMINAR LA SESIÓN 1

### Paso 1 — Crear la D1 en Cloudflare

```cmd
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-worker"
npx wrangler d1 create sertecapp-db
```

→ Copiar el `database_id` que devuelve y reemplazarlo en `wrangler.toml`:
```toml
database_id = "PEGAR-AQUI-EL-ID-REAL"
```

### Paso 2 — Exportar datos del SQLite actual

```cmd
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"
sqlite3 database\database.sqlite .dump > ..\sertecapp-worker\scripts\export_full.sql
```

### Paso 3 — Limpiar el SQL (Claude crea clean_export.js)

Script pendiente de crear: `sertecapp-worker/scripts/clean_export.js`
- Filtra solo las tablas necesarias (no las de Laravel: migrations, jobs, etc.)
- Convierte `$2y$` → `$2b$` en passwords
- Quita triggers y cosas incompatibles con D1
- Genera `export_clean.sql`

### Paso 4 — Aplicar schema y datos en D1 local

```cmd
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-worker"
npx wrangler d1 execute sertecapp-db --local --file=src/db/schema.sql
npx wrangler d1 execute sertecapp-db --local --file=src/db/seed_roles.sql
npx wrangler d1 execute sertecapp-db --local --file=scripts/export_clean.sql
```

### Paso 5 — Verificar datos

```cmd
npx wrangler d1 execute sertecapp-db --local --command="SELECT COUNT(*) as total FROM customers"
npx wrangler d1 execute sertecapp-db --local --command="SELECT COUNT(*) as total FROM parts"
npx wrangler d1 execute sertecapp-db --local --command="SELECT id, name, email FROM users"
```

### Paso 6 — Probar el Worker local

```cmd
npx wrangler dev --local
```
Luego testear:
```
curl -X POST http://localhost:8787/api/login -H "Content-Type: application/json" -d "{\"email\":\"admin@sertecapp.local\",\"password\":\"1234\"}"
curl http://localhost:8787/api/health
```

### Paso 7 — Commit

```cmd
git add -A
git commit -m "feat: worker completo + schema D1 + datos migrados"
```

---

## ESTRUCTURA FINAL DEL WORKER (referencia)

```
sertecapp-worker/src/
├── index.ts              ← Router principal ✅
├── types.ts              ← Interfaces ✅
├── middleware/
│   └── auth.ts           ← JWT + roles ✅
├── utils/
│   ├── jwt.ts            ← Web Crypto sign/verify ✅
│   └── response.ts       ← ok(), err(), paginate() ✅
├── routes/
│   ├── auth.ts           ← POST /api/login, GET /api/me ✅
│   ├── workOrders.ts     ← CRUD completo ✅
│   ├── customers.ts      ← Lista + detalle con equipos ✅
│   ├── equipments.ts     ← Filtrado por customer_id ✅
│   ├── users.ts          ← Lista técnicos/admins ✅
│   ├── parts.ts          ← Lista + búsqueda repuestos ✅
│   └── partesTecnicos.ts ← GET + POST partes con firma ✅
└── db/
    ├── schema.sql         ← Schema D1 completo ✅
    └── seed_roles.sql     ← Roles iniciales ✅
```

---

## CREDENCIALES ACTUALES (sistema en uso)

| Usuario | Email | PIN | Rol |
|---------|-------|-----|-----|
| Admin | admin@sertecapp.local | 1234 | administrador |
| Hugo | pendziuch@gmail.com | 1234 | administrador |
| Juan Técnico | tech@demo.com | 1234 | técnico |

---

## PARA LA PRÓXIMA SESIÓN

1. Empezar con Paso 1 de arriba (crear D1)
2. Claude crea `scripts/clean_export.js` automáticamente
3. Migrar datos, verificar, probar Worker local
4. Si todo ok → deploy Worker a Cloudflare
5. Conectar frontend al Worker (cambiar API_URL)
6. Deploy frontend → demo final para Luis

---

## COMANDOS CLAVE

```cmd
:: Levantar Laravel (si está bajado)
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"
php artisan serve --host=127.0.0.1 --port=8000

:: Levantar frontend local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
npx next dev --port 3002

:: Tunnel (si está bajado)
"C:\Users\Hugo Pendziuch\AppData\Local\Microsoft\WinGet\Packages\Cloudflare.cloudflared_Microsoft.Winget.Source_8wekyb3d8bbwe\cloudflared.exe" tunnel run sertecapp-tunnel

:: Dev Worker local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-worker"
npx wrangler dev --local
```

---

## NOTAS TÉCNICAS IMPORTANTES

- `bcryptjs.compareSync()` — convierte `$2y$` → `$2b$` antes de comparar (Laravel compat)
- El Worker usa Web Crypto nativo para JWT — sin librería externa
- CORS configurado con `*` (cambiar a solo Pages URL en producción)
- `wrangler.toml` tiene `database_id = "placeholder-reemplazar-con-id-real"` — DEBE actualizarse
- `compatibility_flags = ["nodejs_compat"]` necesario para bcryptjs
- Build Worker: `npx wrangler deploy` (no necesita --webpack como Next.js)
- NUNCA usar Windows-MCP:PowerShell para comandos
- SIEMPRE usar Desktop Commander:start_process con shell="cmd"
