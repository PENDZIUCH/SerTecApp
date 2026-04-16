# SerTecApp — Contexto para Claude

> Leer completo antes de hacer cualquier cosa.
> Última actualización: 2026-04-16

---

## ⚠️ REGLAS CRÍTICAS

1. **NUNCA tocar `.env` sin preguntar.** Puede romper la DB en uso.
2. **NUNCA asumir que algo funciona sin verificarlo.** Siempre confirmar.
3. **NUNCA hacer acciones destructivas sin OK explícito del usuario.**
4. **Si el usuario dice que algo funcionaba, creerle.**
5. **Pasar URLs siempre como links** `[texto](url)`, nunca como texto plano.
6. **Antes de cualquier deploy a Hostinger: usar el skill `/deploy-hostinger`.**

---

## Estado actual (2026-04-16) ✅

| Entorno | URL | Estado |
|---------|-----|--------|
| **Admin panel (Hostinger)** | [https://demo.pendziuch.com/sertecapp/login](https://demo.pendziuch.com/sertecapp/login) | ✅ Funciona |
| **API REST (Hostinger)** | [https://demo.pendziuch.com/api/v1](https://demo.pendziuch.com/api/v1) | ✅ Funciona |
| Admin panel (local) | [http://localhost:8000/sertecapp/login](http://localhost:8000/sertecapp/login) | ✅ Funciona |
| PWA técnicos (prod) | [https://sertecapp-tecnicos.pages.dev](https://sertecapp-tecnicos.pages.dev) | ✅ Live |
| API Cloudflare Workers | [https://sertecapp-worker.pendziuch.workers.dev](https://sertecapp-worker.pendziuch.workers.dev) | ✅ Live |

**Login producción:** `pendziuch@gmail.com` / `SerTecApp2026!`

---

## Qué es este proyecto

Sistema de gestión de órdenes de trabajo para servicio técnico de equipos de fitness.
- **Cliente final:** Luis (Fitness Company — reparación equipos fitness, CABA/GBA)
- **Desarrollador:** Hugo Pendziuch (`pendziuch@gmail.com`)
- **GitHub:** [https://github.com/PENDZIUCH/SerTecApp](https://github.com/PENDZIUCH/SerTecApp)
- **Rama activa:** `development`

---

## Stack

| Capa | Tecnología | Directorio |
|------|-----------|------------|
| Admin panel | Laravel 11 + Filament 3.2 + FilamentShield | `backend-laravel/` |
| API REST | Laravel Sanctum (en el mismo backend) | `backend-laravel/routes/api.php` |
| Frontend PWA | Next.js 14 | `sertecapp-tecnicos/` |
| API edge | Cloudflare Workers (TypeScript) | `sertecapp-worker/` |
| DB Hostinger | MySQL `u283281385_sertecappers` | — |
| DB local | MySQL vía Laragon (`sertecapp`) | — |

---

## Skills disponibles (slash commands)

### `/deploy-hostinger [modo]`
Deploy de SerTecApp a Hostinger. Usar **siempre** en vez de hacer pasos manuales.

```
/deploy-hostinger verify      → verifica que todo funciona (30 seg)
/deploy-hostinger update      → deploya últimos commits a Hostinger
/deploy-hostinger first-time  → instalación completa desde cero
```

Definido en: `.claude/skills/deploy-hostinger/SKILL.md`

### `/deploy-laravel-hostinger <dominio> [modo]`
Versión genérica del skill anterior. Funciona para cualquier proyecto Laravel en Hostinger.
Definido en: `~/.claude/skills/deploy-laravel-hostinger/SKILL.md`

---

## Hostinger — datos de conexión

```bash
# SSH
ssh -i ~/.ssh/hostinger_sertecapp -p 65002 u283281385@147.79.103.125

# Deploy manual forzado
~/deploy-sertecapp.sh --force

# Log del último deploy
tail -f /tmp/sertecapp_deploy.log
```

| Dato | Valor |
|------|-------|
| IP | `147.79.103.125` |
| Puerto SSH | `65002` (no el 22 estándar) |
| Key | `~/.ssh/hostinger_sertecapp` |
| Webhook URL | `https://demo.pendziuch.com/deploy.php` |
| Webhook secret | `SerTecDeploy2026!` |

---

## Reglas técnicas Hostinger (lecciones aprendidas)

> Ver manual completo: `.claude/projects/.../memory/MANUAL_DEPLOY_LARAVEL_HOSTINGER.md`

**NUNCA** usar `->path('admin')` en Filament — WAF de Hostinger bloquea todo en `/admin`.
**SIEMPRE** que se cree un modelo User en un proyecto con Filament: implementar `FilamentUser` con `canAccessPanel()`.
**SIEMPRE** agregar `AddHandler application/x-httpd-php83 .php` en `.htaccess` raíz en Hostinger.
`shell_exec`, `exec`, `symlink` están deshabilitados en web PHP de Hostinger — solo funcionan en CLI.

---

## Cómo levantar local

### Laravel (Admin panel + API)
```bash
cd backend-laravel
cp .env.mysql.local .env          # asegurarse de usar MySQL
php artisan serve --port=8000
# → http://localhost:8000/sertecapp/login
```

### Next.js PWA
```bash
cd sertecapp-tecnicos
# .env.local debe tener: NEXT_PUBLIC_API_URL=http://localhost:8000
npm run dev
# → http://localhost:3002
```

---

## DB — datos en producción (Hostinger)

| Tabla | Registros |
|-------|-----------|
| customers | 311 |
| parts | 363 |
| work_orders | 22 |
| users | 5 |
| roles | 7 |

---

## Ramas Git

| Rama | Uso |
|------|-----|
| `development` | Trabajo activo — **siempre trabajar acá** |
| `main` | Producción estable |

Flujo: trabajar en `development` → push → auto-deploy a Hostinger vía webhook.

---

## Auto-deploy (pendiente completar)

El script y webhook ya están en el servidor. Falta:
1. **GitHub Webhook** → [github.com/PENDZIUCH/SerTecApp/settings/hooks/new](https://github.com/PENDZIUCH/SerTecApp/settings/hooks/new)
   - URL: `https://demo.pendziuch.com/deploy.php` | Secret: `SerTecDeploy2026!`
2. **hPanel → Cron Jobs**: `/bin/bash /home/u283281385/deploy-sertecapp.sh` cada 5 min
