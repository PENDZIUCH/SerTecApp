# SESIÓN DEPLOY — sertecapp-tecnicos
**Última actualización:** 2026-03-30  
**Estado:** ✅ FRONTEND DEPLOYADO EN CLOUDFLARE PAGES — FUNCIONANDO

---

## ESTADO ACTUAL

### ✅ Frontend técnicos — Cloudflare Pages (SIN depender de la PC)
- **URL producción:** https://sertecapp-tecnicos.pages.dev
- **Login:** tech@demo.com / PIN 1234
- **Proyecto Cloudflare:** `sertecapp-tecnicos`
- Deploy: `npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true`
- Script rápido: doble click en `deploy.bat`

### ⚠️ Backend — Laravel via Cloudflare Tunnel (REQUIERE PC encendida)
- **URL:** https://sertecapp.pendziuch.com/admin
- **Login admin:** admin@sertecapp.local / password
- **Tunnel:** cloudflared tunnel run sertecapp-tunnel
- **Path:** C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel
- **DB actual:** SQLite con datos reales (5 usuarios, 394 repuestos, 311 clientes, 13 órdenes)

### ❌ pro.pendziuch.com — DESACTIVADO
- Apuntaba al frontend local (puerto 3002) — proceso Next.js terminado
- Para reactivar: apuntar DNS a sertecapp-tecnicos.pages.dev

---

## PRÓXIMA FASE — PROYECTO "STA" (SerTecApp Unificada)

### Concepto
Nueva app unificada para admins Y técnicos, 100% en Cloudflare sin PHP ni servidor propio.

**Nombre del proyecto:** `sta` (o `sertecapp-sta`)
**Stack:** Next.js + Cloudflare Pages + Cloudflare Workers + Cloudflare D1 (SQLite)

### Arquitectura objetivo
```
sta.pendziuch.com (o sta.pages.dev)
        ↓
Login → detecta rol del usuario
        ↓
Admin → dashboard completo (clientes, equipos, órdenes, repuestos, presupuestos)
Técnico → solo sus órdenes asignadas (cargar partes, firmas, fotos)
        ↓
Cloudflare Workers (API — reemplaza Laravel)
        ↓
Cloudflare D1 (SQLite serverless — reemplaza MySQL/SQLite local)
```

### Por qué este stack
- ✅ 100% gratis (Cloudflare Free tier)
- ✅ Sin PC, sin túnel, sin servidor propio
- ✅ SQLite actual se migra directo a D1
- ✅ Deploy con wrangler igual que el frontend
- ✅ Un solo lugar para admins y técnicos
- ✅ Escalable si el negocio crece

### Datos a migrar de SQLite actual a D1
- 5 usuarios (conservar credenciales)
- 394 repuestos (importados de Excel Life Fitness)
- 311 clientes
- 13 órdenes de trabajo

### Lo que reemplaza a Laravel
- Login/auth → Worker con JWT
- API de órdenes → Worker
- API de repuestos → Worker
- API de clientes → Worker
- API de partes técnicos → Worker
- Presupuestos PDF → Worker (o librería JS)

### Plan de desarrollo "STA"
1. Crear proyecto Next.js nuevo en `sta/` dentro del repo
2. Crear Worker de Cloudflare para la API
3. Crear base D1 y migrar datos del SQLite actual
4. Implementar login con roles (admin/técnico)
5. Construir vistas: admin dashboard + técnico dashboard
6. Deploy en Cloudflare Pages como `sta`

### Mientras tanto
- `sertecapp-tecnicos` sigue funcionando para Luis
- Backend Laravel sigue en túnel para el admin
- No se rompe nada existente

---

## CÓMO DEPLOYAR EL FRONTEND ACTUAL (proceso simplificado)

```cmd
:: Doble click en deploy.bat, o manualmente:
pushd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
rmdir /s /q .next
npx next build --webpack
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
```

---

## ESTRUCTURA DE RUTAS DINÁMICAS — IMPORTANTE

Next.js con `output: export` requiere patrón server/client para rutas dinámicas:
- `page.tsx` → wrapper server con `generateStaticParams`, `dynamic = 'force-static'`, `dynamicParams = false`
- `_client.tsx` → código real con `'use client'`
- **NO crear `page-client.tsx`** — Next.js lo trata como página y rompe el build

---

## NOTAS TÉCNICAS

- Node v24.11.0, npm 11.6.1, wrangler 4.56.0
- `output: 'export'` + `trailingSlash: true` en `next.config.ts`
- `npx next build --webpack` — SIEMPRE con --webpack (next-pwa incompatible con Turbopack)
- API URL hardcodeada: `https://sertecapp.pendziuch.com`
- Comandos: **Desktop Commander:start_process con shell="cmd"** — NUNCA Windows-MCP:PowerShell
