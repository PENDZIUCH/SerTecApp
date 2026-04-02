# SESIÓN DEPLOY — sertecapp-tecnicos
**Última actualización:** 2026-04-02
**Estado:** ✅ PRODUCCIÓN EN CLOUDFLARE — sin túnel, sin Laravel, sin PC encendida

---

## ESTADO ACTUAL — TODO EN CLOUDFLARE

| Componente | URL | Estado |
|-----------|-----|--------|
| Frontend PWA | https://sertecapp-tecnicos.pages.dev | ✅ LIVE |
| API Worker | https://sertecapp-worker.pendziuch.workers.dev | ✅ LIVE |
| Base de datos | Cloudflare D1 (sertecapp-db) | ✅ LIVE |
| Túnel Laravel | sertecapp.pendziuch.com | ❌ APAGADO (no necesario) |

### Datos en D1 producción
- 311 clientes, 394 repuestos, 5 usuarios con roles, 6 órdenes
- Status de órdenes normalizados en español (pendiente/completado/en_progreso)

---

## CREDENCIALES

| Usuario | Email | PIN | Rol |
|---------|-------|-----|-----|
| Admin | admin@sertecapp.local | 1234 | administrador |
| Hugo | pendziuch@gmail.com | 1234 | administrador |
| Juan Técnico | tech@demo.com | 1234 | técnico |

---

## FLUJO DE TRABAJO PARA NUEVOS CAMBIOS

### Desarrollo local
```cmd
:: 1. Apuntar al Worker local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"
php switch_api.php local

:: 2. Worker local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-worker"
npx wrangler dev --local --port 8787

:: 3. Frontend local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
npx next dev --port 3002
```

### Deploy a producción (solo cuando está testeado local)
```cmd
:: Worker
php backend-laravel\switch_api.php worker
cd sertecapp-worker && npx wrangler deploy

:: Frontend
cd sertecapp-tecnicos
set NEXT_EXPORT=1
rmdir /s /q .next & rmdir /s /q out
npx next build --webpack
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
```

### Rollback de emergencia a Laravel
```cmd
php backend-laravel\switch_api.php laravel
:: + deploy frontend (5 min) → vuelve a Laravel
:: Encender túnel: cloudflared tunnel run sertecapp-tunnel
```

---

## BUGS RESUELTOS HOY

| Bug | Causa | Fix |
|-----|-------|-----|
| "Application error" en prod | `config.ts` fallback era localhost:8787 | Fallback al Worker de producción |
| Técnico no veía sus órdenes | Worker devolvía formato admin (customer anidado) | `fmtTecnico()` con clientName, problem, address planos |
| Status "completed"/"pending" en inglés | D1 tenía valores de Laravel sin normalizar | UPDATE masivo en D1 local y remota |
| POST partes fallaba | Worker esperaba `work_order_id`, frontend mandaba `orden_id` | Acepta ambos + campos en español |

---

## NOTAS TÉCNICAS

- `set NEXT_EXPORT=1` va en comando SEPARADO antes del build (no con &&)
- `switch_api.php local` → localhost:8787 (Worker dev)
- `switch_api.php worker` → Worker Cloudflare (producción)
- `switch_api.php laravel` → sertecapp.pendziuch.com (fallback)
- Worker acepta /api/v1/... y /api/... indistintamente
- Worker acepta `orden_id` y `work_order_id` en POST /api/partes
- `config.ts` tiene fallback al Worker de producción (NUNCA localhost)
- NUNCA usar Windows-MCP:PowerShell para comandos
- SIEMPRE usar Desktop Commander:start_process con shell="cmd"

---

## PRÓXIMAS SESIONES

### Sesión 5 — Admin mínimo en PWA
- [ ] Gestión usuarios (crear técnicos, editar, desactivar)
- [ ] Gestión clientes (crear, editar)
- [ ] Ver partes completados desde admin con firma
- [ ] Endpoints Worker: POST/PUT /api/users, POST/PUT /api/customers

### Sesión 6 — Importación Excel
### Sesión 7 — Verificación final + tag v2.0.0
