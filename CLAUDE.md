# SerTecApp — Contexto para Claude

> Leer completo antes de hacer cualquier cosa.
> Última actualización: 2026-09-03
> **Este archivo ES la memoria del proyecto — la única fuente de verdad que viaja entre chats, terminales y sesiones.** Cualquier chat de claude.ai tiene además su propia memoria interna, pero esa no la ve una sesión de Claude Code en la terminal — así que todo lo importante y durable se escribe ACÁ, no solo en el chat.

---

## ⚠️ REGLAS CRÍTICAS

0. **Antes de decir nada sobre el estado del proyecto: verificar en vivo** (`git log`, `git reflog`, `curl` a las URLs, leer código fuente). Las secciones de "estado" de este archivo se desactualizan — úsalas como punto de partida, no como verdad absoluta. Nunca narrar una conclusión sin haberla chequeado contra algo real en esta sesión.
1. **NUNCA tocar `.env` sin preguntar.** Puede romper la DB en uso.
2. **NUNCA asumir que algo funciona sin verificarlo.** Siempre confirmar.
3. **NUNCA hacer acciones destructivas sin OK explícito del usuario.**
4. **Si el usuario dice que algo funcionaba, creerle.**
5. **Pasar URLs siempre como links** `[texto](url)`, nunca como texto plano.
6. **NUNCA mergear ramas (`git merge`) sin mostrar antes `git diff --stat <rama1>..<rama2>` y pedir confirmación si el diff toca más de ~20 archivos o carpetas fuera del alcance del feature.** Un merge mal hecho puede arrastrar código legacy de vuelta al repo sin que nadie lo note hasta días después.
7. **Un commit firmado con el nombre/email de Hugo en `git log` NO prueba que Hugo lo haya hecho.** Hugo no usa git manualmente. Cualquier commit hecho por una sesión de Claude vía terminal en su máquina queda firmado con la identidad de git configurada localmente. Nunca atribuirle a Hugo una acción solo por la autoría del commit.
8. **`backend-laravel/` (deploy a Hostinger vía push a `development`) y `sertecapp-tecnicos/` (deploy a Cloudflare Pages vía `wrangler pages deploy`) son pipelines INDEPENDIENTES.** Un push que dispara el webhook de Hostinger NO publica cambios del frontend Next.js. Si se toca `sertecapp-tecnicos/`, hay que build+deploy a Cloudflare aparte y decirlo explícitamente.
9. **Antes de cualquier deploy a Hostinger: usar el skill `/deploy-hostinger`.**
10. **Al cerrar cualquier sesión donde se avanzó algo real: actualizar este archivo antes de terminar.** No dejarlo para "la próxima" — la próxima sesión no tiene memoria de esta conversación.

---

## 📋 Incidente 2026-09-02/03 — merge que ensució `main` (RESUELTO)

Una sesión de Claude hizo `git merge development` sobre `main` para subir 3 features (ojito PWA, sesión persistente, permisos supervisor) y arrastró 509 archivos / +27.193 líneas de una carpeta Laravel legacy en la raíz (`app/`, `config/`, `database/`, `routes/`, `resources/`, `storage/`, `bootstrap/`, `public/`, `tests/`, `artisan`, `composer.json` — sin `vendor/` ni `.env`, no ejecutable, no relacionada con `backend-laravel/` que es el Laravel real). Después un `reset` dejó `main` y `development` en el mismo commit.

**Corregido el 2026-09-03:**
- Carpeta legacy movida a `ARCHIVOS/laravel-legacy-julio/` (commit `a1b6e52`), confirmado sin impacto en producción.
- `test-sertecapp.bat` creado en la raíz para chequear infraestructura completa en 1 llamado (ver reglas de testing rápido).
- Confirmado: ojito PWA + sesión persistente + permisos supervisor SIGUEN sin buildear/deployar a Cloudflare Pages — código listo, no publicado.
- `main` = lo vivo en `sertecapp.pendziuch.com`/Hostinger. `development` = prueba/local. Idea futura: subdominio de staging separado.
- Ver reglas 6, 7 y 8 arriba — nacieron de este incidente.

---

## Estado actual verificado (2026-09-02) ✅

**Dos frontends PWA — a propósito, no es un error:**

| Frontend | URL | API a la que apunta | Rol |
|---|---|---|---|
| **Activo (actualizar siempre acá)** | [https://sertecapp.pendziuch.com](https://sertecapp.pendziuch.com) | `https://demo.pendziuch.com` (Hostinger, MySQL) | El que se sigue desarrollando |
| Backup de demo (congelado a propósito) | [https://sertecapp-tecnicos.pages.dev](https://sertecapp-tecnicos.pages.dev) | `https://sertecapp-worker.pendziuch.workers.dev` (Worker viejo + D1/SQLite) | No tocar ni preocuparse si queda "atrás" |

| Entorno | URL | Estado |
|---------|-----|--------|
| Admin panel (Hostinger) | [https://demo.pendziuch.com/sertecapp/login](https://demo.pendziuch.com/sertecapp/login) | ✅ Funciona |
| API REST (Hostinger) | [https://demo.pendziuch.com/api/v1](https://demo.pendziuch.com/api/v1) | ✅ Funciona |
| Admin panel (local) | [http://localhost:8000/sertecapp/login](http://localhost:8000/sertecapp/login) | Verificar si está levantado (`netstat`) antes de asumir |

**Git:** `main` y `development` sincronizados en el mismo commit al 2026-09-02 18:41 (verificar con `git log -1` en cada uno — no asumir que están desalineados).

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

---

## ⚠️ Regla agregada 2026-07-28

**NUNCA hardcodear paths del panel Filament** (ej: `/sertecapp/`, `/admin/`).
Siempre usar `NombreResource::getUrl('index')` en widgets y cualquier link interno.
Razón: si cambia el nombre del panel o el dominio, los links se rompen.
Corrección aplicada en `StatsOverviewWidget.php` — commit `0e5e352`.

---

## Estado actualizado 2026-07-28

### Deploy funcionando
- Git en Hostinger ahora trackea todos los archivos — `git pull` actualiza correctamente
- Flujo de deploy: fix local → commit → push → SSH `git pull origin development && php artisan view:clear && php artisan cache:clear`
- Webhook automático pendiente (conectar GitHub → `https://demo.pendziuch.com/deploy.php`)

### Fixes aplicados hoy (local + Hostinger)
- Widget dashboard: URLs dinámicas via `Resource::getUrl()` — no depende del nombre del panel
- Técnico asignado obligatorio en crear/editar órdenes
- Títulos de páginas WorkOrder en español
- Notificación de parte en try-catch separado — no bloquea el guardado
- CORS: orígenes explícitos en lugar de wildcard con credentials
- URL "Ver Parte" dinámica via `WorkPartResource::getUrl()`

### Flujo completo verificado en local Y Hostinger
- Coordinador crea orden en Filament → técnico la ve en PWA → técnico completa parte → aparece en Filament ✅

### Pendiente próxima sesión
- Configurar webhook GitHub → deploy.php para auto-deploy
- Traducir títulos del resto de resources (WorkPart, Customer, etc.)
- Deploy PWA conectada a Hostinger en Cloudflare Pages separado
- Jerarquía de roles: solo superadmin puede crear otros superadmin

---

## Problema pendiente — estructura del repo (RESOLVER PRIMERO en próxima sesión)

**El problema:** El repo tiene archivos en dos paths distintos:
- Local trackea: `backend-laravel/app/Filament/...` (repo root = `SerTecApp/`)
- Hostinger trackea: `app/Filament/...` (repo root = `backend-laravel/`)

Por eso `git pull` en Hostinger nunca actualiza los archivos de Filament correctamente.

**La solución (Opción B — próxima sesión):**
Reorganizar Hostinger para que el repo root sea `SerTecApp/` igual que local, y el deploy apunte a `public_html/backend-laravel/`. Así git pull funciona de verdad.

**Lo que NO hacer mientras tanto:**
- No commitear desde Hostinger — pisa los fixes de local
- No hacer git reset --hard desde Hostinger

**Estado actual de Hostinger:**
- Dashboard funciona ✅
- Links sin 404 ✅  
- Técnico obligatorio ✅
- Títulos en inglés todavía (ListWorkOrders, etc.) ⚠️

---

## SOLUCIÓN DEFINITIVA AL PROBLEMA DE DEPLOY (2026-07-28)

### El problema
El repo tiene archivos en DOS paths distintos:
- `app/Filament/...` — path que usa Hostinger (repo root = `backend-laravel/`)
- `backend-laravel/app/Filament/...` — path local (repo root = `SerTecApp/`)

`git pull` en Hostinger solo actualiza los archivos en `app/...` — nunca los de `backend-laravel/app/...`.

### La solución
Antes de cada commit que toca archivos PHP, correr robocopy para sincronizar:

```cmd
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"
robocopy backend-laravel . /E /XO /XF .env /XD vendor node_modules .git storage
git add app/
git commit -m "fix: descripción del cambio"
git push origin development
```

Luego en SSH de Hostinger:
```bash
git pull origin development && php artisan view:clear && php artisan cache:clear
```

### Archivos que NO sincronizar con robocopy
- `.env` — cada entorno tiene el suyo
- `vendor/` — se instala con composer
- `storage/` — datos locales
- `.git/` — repositorio git

### Pendiente — automatizar con script
Crear un script `deploy.ps1` local que haga todo en un comando:
1. robocopy backend-laravel → app/
2. git add + commit + push
3. SSH a Hostinger → git pull + artisan clear

### Pendiente — recursos en inglés
Traducir Pages de todos los resources:
- WorkPart (Partes pendientes)
- Customer (Clientes)
- Equipment (Equipos)
- Part (Repuestos)
- Visit (Visitas)
- User (Usuarios)

---

## Sesión 2026-09-02 — avances y aprendizajes

### Avances (commits en `development`, ya en `main` también)
- `3ff27ea` — login PWA con sesión persistente ("Hola Juan") + WhatsApp con email, pass temporal y magic link
- `3785486` — ojito mostrar/ocultar contraseña en login PWA

### Incidente: merge a `main` y reversión
Se hizo `merge development → main` (18:29) y después se pidió deshacerlo con `reset` (18:41).
Resultado: **`main` y `development` quedaron sincronizados en `3785486`, sin pérdida de commits.** Si en una futura sesión algo parece "faltar", correr `git log -1` en ambas ramas antes de asumir que se perdió trabajo — probablemente no se perdió nada.

### Aclaración importante: los dos frontends PWA no están en conflicto
`sertecapp.pendziuch.com` (activo, Hostinger/MySQL) y `sertecapp-tecnicos.pages.dev` (backup de demo congelado, Worker viejo + D1/SQLite) apuntan a APIs distintas a propósito — ver tabla en "Estado actual verificado" arriba. Confirmado comparando `.env.production.local` (`NEXT_PUBLIC_API_URL=https://demo.pendziuch.com`) contra el JS bundle servido en pages.dev (que llama a `sertecapp-worker.pendziuch.workers.dev`).

### Cabos sueltos detectados (no resueltos todavía)
- `backend-laravel/deploy.bat` — vacío (0 bytes), creado 06/08. **Intento fallido, nunca se completó.** Candidato a borrar (pendiente OK de Hugo).
- `backend-laravel/fix_wps.py` — script Python del 08/08 que intentaba generar `app/Services/WorkPartService.php` (lógica de: técnico envía parte → notifica supervisor → aprobar/rechazar → emails). **Intento fallido:** el PHP embebido tiene las variables rotas (faltan los `$`), y `WorkPartService.php` **no existe en ningún lado del repo hoy**. Candidato a borrar (pendiente OK de Hugo).
- **🔴 Duplicación de `app/Filament/` sin resolver — más grave de lo que se pensaba.** Verificado en vivo: existen DOS copias, `app/Filament/` (raíz del repo) y `backend-laravel/app/Filament/`, y **ya están desincronizadas** (`WorkPartResource.php` es distinto entre las dos). Este es el mismo problema documentado en la sección "Problema pendiente — estructura del repo" más abajo — sigue sin resolverse a pesar de que se le dedicó tiempo antes. Riesgo: Hostinger puede estar sirviendo código viejo sin que se note. **Prioridad para próxima sesión de trabajo real.**
- El repo raíz tiene ~15 archivos `.md` sueltos de sesiones de deploy anteriores (`RESUMEN_SOLUCION_DEPLOY.md`, `SOLUCION_DEPLOY_DEVELOPMENT.md`, `CHECKLIST_DEPLOY.md`, `CLAUDE_ES_UN_BOLUDO.md`, etc.) — información dispersa que compite con este archivo como fuente de verdad. Sugerido: mover a `docs/archivo/` y dejar `CLAUDE.md` como único punto de entrada (pendiente OK de Hugo).
