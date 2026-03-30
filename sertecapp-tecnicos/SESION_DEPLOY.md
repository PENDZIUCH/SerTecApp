# SESIÓN DEPLOY — sertecapp-tecnicos
**Última actualización:** 2026-03-30  
**Estado:** ✅ FRONTEND DEPLOYADO EN CLOUDFLARE PAGES — FUNCIONANDO

---

## ESTADO ACTUAL

### ✅ Frontend — Cloudflare Pages (SIN depender de la PC)
- **URL producción:** https://sertecapp-tecnicos.pages.dev
- **Login:** tech@demo.com / PIN 1234
- **Proyecto Cloudflare:** `sertecapp-tecnicos`
- Deployado con `npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true`

### ⚠️ Backend — Laravel via Cloudflare Tunnel (REQUIERE PC encendida)
- **URL:** https://sertecapp.pendziuch.com/admin
- **Login admin:** admin@sertecapp.local / password
- **Tunnel:** cloudflared tunnel run sertecapp-tunnel
- **Backend path:** C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel
- Pendiente: deploy del backend a hosting externo para eliminar dependencia de la PC

### ❌ pro.pendziuch.com — DESACTIVADO
- Apuntaba al frontend local (puerto 3002) via túnel
- El proceso Next.js dev fue terminado — ese dominio ya no sirve nada
- Cuando se quiera usar, apuntarlo a Cloudflare Pages (cambio DNS en Cloudflare)

---

## ARQUITECTURA OFFLINE — YA IMPLEMENTADA

La app tiene soporte offline completo en `app/lib/storage.ts`:
- `saveParteLocal()` — guarda partes en localStorage sin red
- `syncPendingPartes()` — sincroniza automáticamente al volver la conexión
- `cacheOrdenes()` — órdenes disponibles 24hs sin red
- `setupConnectionListener()` — detecta cambios de conectividad
- `useOnlineStatus()` hook — ping al backend cada 10s, modo force-offline

**Limitación conocida:** el técnico debe estar logueado ANTES de quedarse sin red.
Una vez logueado, puede trabajar offline y los partes se sincronizan solos al volver la conexión.

---

## CÓMO DEPLOYAR (PROCESO SIMPLIFICADO)

Cada vez que se hagan cambios al frontend y se quiera deployar:

### Paso 1 — Build
```cmd
:: Abrir terminal y ejecutar:
pushd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
rmdir /s /q .next
npx next build --webpack
```

### Paso 2 — Deploy
```cmd
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
```

### Paso 3 — Verificar
Abrir https://sertecapp-tecnicos.pages.dev y probar login.

**Nota:** wrangler ya está autenticado. Si falla auth, correr `npx wrangler login` primero.

---

## ESTRUCTURA DE RUTAS DINÁMICAS — IMPORTANTE

Next.js con `output: export` requiere patrón server/client para rutas dinámicas:

### app/detalle/[id]/
- `page.tsx` — wrapper server con `generateStaticParams` y `dynamic = 'force-static'`
- `_client.tsx` — código real con `'use client'`

### app/parte/[id]/
- `page.tsx` — wrapper server con `generateStaticParams` y `dynamic = 'force-static'`
- `_client.tsx` — código real con `'use client'`

**NO crear archivos `page-client.tsx`** en estas carpetas — Next.js los trata como páginas y rompe el build.

---

## NOTAS TÉCNICAS

- Node v24.11.0, npm 11.6.1, wrangler 4.56.0
- `output: 'export'` + `trailingSlash: true` en `next.config.ts`
- `npx next build --webpack` — SIEMPRE con --webpack (next-pwa no es compatible con Turbopack)
- API URL hardcodeada en la app: `https://sertecapp.pendziuch.com`
- Para ejecutar comandos: **Desktop Commander:start_process con shell="cmd"**
- NUNCA usar Windows-MCP:PowerShell — siempre falla

---

## PRÓXIMOS PASOS (cuando se retome)

1. **Apuntar `pro.pendziuch.com` a Cloudflare Pages** — cambio DNS simple, 2 minutos
2. **Deploy backend Laravel** a hosting externo para eliminar dependencia del túnel
3. **Verificar sincronización offline** — probar flujo completo: guardar parte sin red, volver a conectar, confirmar sync
