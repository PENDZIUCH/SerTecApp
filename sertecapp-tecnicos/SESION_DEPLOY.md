# SESIÓN DEPLOY — sertecapp-tecnicos
**Última actualización:** 2026-04-01
**Estado:** ✅ FUNCIONAL EN PRODUCCIÓN — técnico puede crear partes

---

## ESTADO ACTUAL

### ✅ Funciona en https://sertecapp-tecnicos.pages.dev
- Login con detección de rol (admin → /admin, técnico → /ordenes)
- Técnico ve sus órdenes asignadas
- Técnico crea parte con firma → guardado en backend Laravel
- Admin crea órdenes (modal inline)
- Admin edita órdenes (/admin/orden?id=X) — status normalizado inglés→español
- Vista Técnico desde /admin sin navegar fuera (SPA, botón volver)
- Service worker con auto-update: detecta nueva versión y recarga solo

### ✅ Backend Laravel (requiere PC encendida + túnel)
- URL: https://sertecapp.pendziuch.com
- DB: SQLite con 5 usuarios, 394 repuestos, 311 clientes, órdenes reales

### ✅ Build/Deploy
- next.config.dev.ts.bak y next.config.production.ts.bak — renombrados (causaban error TS)
- Siempre: set NEXT_EXPORT=1 PRIMERO (comando separado), luego npx next build --webpack
- Deploy: npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true

---

## BUGS RESUELTOS HOY

| Bug | Causa | Fix |
|-----|-------|-----|
| /parte/19 → 404 | Carpetas [id] en out/ del build viejo | Borradas parte/[id] y detalle/[id], nuevo build |
| Status "completed" en select admin | Backend devuelve inglés | normalizeStatus() en _client.tsx |
| Vista Técnico → sin vuelta atrás | router.push('/ordenes') navegaba fuera | Estado `vista` en admin/page.tsx, SPA puro |
| SW no se actualizaba en PWA instalada | Cache names iguales, 0 files subidos | Cache names v2, borrar out/ fuerza rebuild total |
| Build falla TS | next.config.dev.ts y .production.ts en raíz | Renombrados a .bak |

---

## CREDENCIALES

| Usuario | Email | PIN | Rol |
|---------|-------|-----|-----|
| Admin | admin@sertecapp.local | 1234 | administrador |
| Hugo | pendziuch@gmail.com | 1234 | administrador |
| Juan Técnico | tech@demo.com | 1234 | técnico |

---

## CÓMO LEVANTAR LOCAL

```cmd
:: Terminal 1 — Backend Laravel
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"
php artisan serve --host=127.0.0.1 --port=8000

:: Terminal 2 — Túnel Cloudflare
"C:\Users\Hugo Pendziuch\AppData\Local\Microsoft\WinGet\Packages\Cloudflare.cloudflared_Microsoft.Winget.Source_8wekyb3d8bbwe\cloudflared.exe" tunnel run sertecapp-tunnel

:: Terminal 3 — Frontend (dev, sin flags extra)
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
npx next dev --port 3002
```

## CÓMO DEPLOYAR

```cmd
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
set NEXT_EXPORT=1
rmdir /s /q .next
rmdir /s /q out
npx next build --webpack
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
```

---

## PRÓXIMAS SESIONES

### Prioridad 1 — Worker + D1 (independizarse del túnel)
Ver SESION_WORKER_S1.md — código 100% listo, falta:
1. npx wrangler d1 create sertecapp-db → obtener database_id real
2. Exportar SQLite → limpiar → importar a D1
3. Probar Worker local → deploy → conectar frontend

### Prioridad 2 — UX mejoras
- Admin ver el parte completado (diagnóstico, firma) desde /admin
- Mejor feedback cuando parte se guarda offline
- Indicador visual de sincronización pendiente

### Prioridad 3 — Offline más robusto
- Cachear lista de clientes/equipos para crear partes offline
- Queue de órdenes pendientes visible para el técnico
