# SESIÓN DEPLOY — sertecapp-tecnicos
**Última actualización:** 2026-04-01
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
- 311 clientes
- 394 repuestos
- 5 usuarios con roles
- 6 órdenes (las 19 originales tenían FK issues, solo 6 migraron limpio)
- Marcas, modelos, equipos

---

## CREDENCIALES

| Usuario | Email | PIN | Rol |
|---------|-------|-----|-----|
| Admin | admin@sertecapp.local | 1234 | administrador |
| Hugo | pendziuch@gmail.com | 1234 | administrador |
| Juan Técnico | tech@demo.com | 1234 | técnico |

---

## FLUJO DE TRABAJO PARA NUEVOS CAMBIOS

### Desarrollo local seguro
```cmd
:: 1. Apuntar frontend al Worker local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"
php switch_api.php local

:: 2. Levantar Worker local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-worker"
npm install  (si es primera vez)
npx wrangler dev --local --port 8787

:: 3. Levantar frontend local
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
npx next dev --port 3002

:: 4. Probar en http://localhost:3002
```

### Deploy a producción (solo cuando está testeado)
```cmd
:: 1. Apuntar a Worker producción
php backend-laravel\switch_api.php worker

:: 2. Deploy Worker (si cambió)
cd sertecapp-worker && npx wrangler deploy

:: 3. Deploy frontend
cd sertecapp-tecnicos
set NEXT_EXPORT=1
rmdir /s /q .next
rmdir /s /q out
npx next build --webpack
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
```

### Rollback a Laravel (emergencia)
```cmd
php backend-laravel\switch_api.php laravel
:: + deploy frontend → vuelve a Laravel en 5 minutos
:: (requiere encender túnel: cloudflared tunnel run sertecapp-tunnel)
```

---

## NOTAS TÉCNICAS IMPORTANTES

- `set NEXT_EXPORT=1` debe ir en comando SEPARADO antes del build
- `next.config.dev.ts.bak` y `next.config.production.ts.bak` — NO renombrar, causaban error TS
- `switch_api.php local` → apunta a localhost:8787 (Worker dev)
- `switch_api.php worker` → apunta al Worker de Cloudflare (producción)
- `switch_api.php laravel` → apunta a sertecapp.pendziuch.com (fallback)
- Worker acepta /api/v1/... y /api/... indistintamente (normalizado en index.ts)
- JWT_SECRET configurado en Wrangler Secrets (producción)
- `.dev.vars` tiene JWT_SECRET para desarrollo local
- NUNCA usar Windows-MCP:PowerShell para comandos
- SIEMPRE usar Desktop Commander:start_process con shell="cmd"

---

## PRÓXIMAS SESIONES — MVP_CLOUDFLARE.md

### Sesión 5 — Admin mínimo en PWA (pendiente)
- Gestión usuarios (crear técnicos, editar, desactivar)
- Gestión clientes (crear, editar)
- Gestión equipos (crear, asignar a cliente)
- Ver partes completados desde admin

### Sesión 6 — Importación Excel
- Importar clientes desde Excel
- Importar repuestos desde Excel

### Sesión 7 — Verificación final
- Checklist completo sin túnel
- Tag v2.0.0
