# SESIÓN DEPLOY — sertecapp-tecnicos
**Última actualización:** 2026-03-31
**Estado:** ✅ LOCAL FUNCIONANDO — ⏳ DEPLOY PENDIENTE PARA MAÑANA

---

## ESTADO ACTUAL HOY

### ✅ Lo que funciona en localhost:3002
- Login con detección de rol (admin → /admin, técnico → /ordenes)
- Dashboard admin con stats (órdenes, clientes, repuestos)
- Crear orden desde admin con selects dinámicos (cliente → equipo filtrado)
- Editar orden con datos precargados (cliente, técnico, estado, prioridad)
- Crear parte técnico desde /parte?id=X con firma y offline support
- Vista técnico con órdenes asignadas
- Mensajes de error en español

### ✅ Backend Laravel (requiere PC encendida + túnel)
- URL: https://sertecapp.pendziuch.com
- Tunnel: cloudflared en sertecapp-tunnel
- DB: SQLite con 5 usuarios, 394 repuestos, 311 clientes, 13+ órdenes
- Todos los controllers arreglados (sin applyFilters)

### ⏳ Deploy pendiente para mañana
- Frontend en Cloudflare Pages: sertecapp-tecnicos.pages.dev
- Usar deploy.bat — hace todo automático
- NO deployar hasta probar bien local

### ❌ pro.pendziuch.com
- Desactivado — apuntaba al frontend local (puerto 3002)
- Para reactivar: apuntar DNS a sertecapp-tecnicos.pages.dev

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
cd "C:\Users\Hugo Pendziuch\AppData\Local\Microsoft\WinGet\Packages\Cloudflare.cloudflared_Microsoft.Winget.Source_8wekyb3d8bbwe"
cloudflared.exe tunnel run sertecapp-tunnel

:: Terminal 3 — Frontend Next.js
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
npx next dev --port 3002
```

**IMPORTANTE:** next.config.ts NO debe tener output:export ni next-pwa en dev.
El config correcto para dev es simplemente: `module.exports = {}`

---

## CÓMO DEPLOYAR (cuando esté listo)

```cmd
:: Doble click en:
C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos\deploy.bat
```

El deploy.bat hace:
1. Limpia .next
2. Build con NEXT_EXPORT=1 y --webpack (activa output:export y next-pwa)
3. Deploy a Cloudflare Pages sertecapp-tecnicos

---

## RUTAS DE LA APP

| Ruta | Descripción |
|------|-------------|
| / | Login |
| /ordenes | Vista técnico (sus órdenes) |
| /parte?id=X | Crear parte técnico |
| /detalle?id=X | Ver detalle de orden |
| /admin | Dashboard admin |
| /admin/orden?id=X | Editar orden |

**REGLA:** Rutas dinámicas usan SIEMPRE query params (?id=X), nunca /ruta/[id]

---

## BUGS CONOCIDOS / PENDIENTES

- [ ] Status "completed" vs "completado" — el backend a veces devuelve "completed" en inglés
- [ ] pro.pendziuch.com apuntar a Pages después del deploy
- [ ] Migración backend a independiente del túnel (plan abajo)

---

## PLAN MIGRACIÓN — INDEPENDIZARSE DEL BACKEND (próximas sesiones)

### Objetivo
Eliminar dependencia de la PC encendida. Todo en Cloudflare, gratis, siempre online.

### Stack objetivo
```
sertecapp-tecnicos.pages.dev
         ↓
Cloudflare Workers (API — reemplaza Laravel)
         ↓
Cloudflare D1 (SQLite serverless — reemplaza SQLite local)
```

### Fases

#### Fase 1 — Crear infraestructura Cloudflare (1 sesión)
1. Crear base D1 en Cloudflare: `npx wrangler d1 create sertecapp-db`
2. Exportar SQLite actual a SQL: `sqlite3 database.sqlite .dump > export.sql`
3. Limpiar el SQL (quitar triggers incompatibles con D1)
4. Importar a D1: `npx wrangler d1 execute sertecapp-db --file=export.sql`
5. Verificar que los 311 clientes, 394 repuestos, 5 usuarios estén en D1

#### Fase 2 — Crear Worker API (2-3 sesiones)
Endpoints a implementar en Workers (JS/TypeScript):

**Auth:**
- POST /api/login → verificar usuario en D1, devolver JWT
- GET /api/me → datos del usuario logueado

**Órdenes:**
- GET /api/work-orders → lista paginada con filtros
- POST /api/work-orders → crear orden
- PUT /api/work-orders/:id → editar orden
- POST /api/work-orders/:id/change-status → cambiar estado

**Clientes:**
- GET /api/customers → lista paginada

**Equipos:**
- GET /api/equipments → lista, filtrable por customer_id

**Usuarios/Técnicos:**
- GET /api/users → lista con roles

**Repuestos:**
- GET /api/parts → lista paginada

**Partes técnicos:**
- POST /api/partes → guardar parte
- GET /api/partes/:orden_id → obtener parte de una orden

#### Fase 3 — Conectar frontend al Worker (1 sesión)
- Cambiar API_URL de `https://sertecapp.pendziuch.com` al Worker URL
- Probar todos los flujos
- Deploy final

#### Fase 4 — Dominio (30 minutos)
- Apuntar pro.pendziuch.com a Cloudflare Pages
- Apuntar api.pendziuch.com al Worker

### Datos a migrar
- 5 usuarios (con passwords hasheadas — necesitan rehash o reset)
- 394 repuestos (importados de Excel Life Fitness)
- 311 clientes
- Equipos y órdenes existentes

### Consideraciones
- Los passwords en SQLite son bcrypt de Laravel — hay que reimplementar bcrypt en el Worker o resetearlos todos
- JWT en Workers es simple con la librería jose
- D1 tiene limitaciones: no soporta algunos tipos de SQLite (pero el schema de Laravel es compatible)

---

## NOTAS TÉCNICAS

- Node v24.11.0, npm 11.6.1, wrangler 4.56.0
- next-pwa@5.6.0 — solo se usa en BUILD de producción
- Build producción: NEXT_EXPORT=1 npx next build --webpack
- Dev local: npx next dev --port 3002 (sin flags)
- Comandos: Desktop Commander:start_process con shell="cmd"
- NUNCA Windows-MCP:PowerShell
- Windows-MCP:FileSystem para leer/escribir archivos
- Para strings con template literals usar node fix.js en vez de Windows-MCP (escapa backslashes)
