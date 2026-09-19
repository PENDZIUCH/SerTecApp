# SerTecApp — Contexto para Claude

> Leer completo antes de hacer cualquier cosa.
> Última actualización: 2026-09-19
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
8. **`backend-laravel/` (deploy a Hostinger vía webhook) y `sertecapp-tecnicos/` (deploy a Cloudflare Pages) son pipelines INDEPENDIENTES, pero desde 2026-09-04 los DOS son automáticos con un solo push a `development`.** Ver sección "Deploy automático de la PWA (Cloudflare Pages)" más abajo — ya no hace falta `wrangler pages deploy` a mano.
9. **Antes de cualquier deploy a Hostinger: usar el skill `/deploy-hostinger`.**
10. **Al cerrar cualquier sesión donde se avanzó algo real: actualizar este archivo antes de terminar.** No dejarlo para "la próxima" — la próxima sesión no tiene memoria de esta conversación.
11. **No hardcodear dominios/URLs en código nuevo — usar variables de entorno con un fallback razonable.** Acordado con Hugo el 2026-09-07 tras encontrar `https://pro.pendziuch.com` pegado a mano en el Open Graph de la PWA (dominio ni siquiera vigente). Este es un producto pensado para reusarse (ver "Convergencia con core/v1" más abajo) — el dominio va a cambiar más de una vez. Patrón: `backend-laravel` ya usa `config('app.pwa_url')`/`config('app.url')` (env `PWA_URL`/`APP_URL`); `sertecapp-tecnicos` centraliza esto en `lib/config.ts` (`API_URL`, `APP_URL`) — nunca escribir la URL directo en un componente o controller.

---

## 📋 Incidente 2026-09-02/03 — merge que ensució `main` (RESUELTO)

Una sesión de Claude hizo `git merge development` sobre `main` para subir 3 features (ojito PWA, sesión persistente, permisos supervisor) y arrastró 509 archivos / +27.193 líneas de una carpeta Laravel legacy en la raíz (`app/`, `config/`, `database/`, `routes/`, `resources/`, `storage/`, `bootstrap/`, `public/`, `tests/`, `artisan`, `composer.json` — sin `vendor/` ni `.env`, no ejecutable, no relacionada con `backend-laravel/` que es el Laravel real). Después un `reset` dejó `main` y `development` en el mismo commit.

**Corregido el 2026-09-03:** carpeta legacy movida a `ARCHIVOS/laravel-legacy-julio/` (commit `a1b6e52`), confirmado sin impacto en producción. `test-sertecapp.bat` creado en la raíz para chequear infraestructura completa en 1 llamado.

**Completado el 2026-09-04:** esa limpieza había quedado a medias — el commit `a1b6e52` archivó `app/config/database/resources/routes` pero dejó sueltos en la raíz el resto del mismo esqueleto legacy (`bootstrap/`, `public/`, `storage/`, `tests/`, `artisan`, `composer.json`, `composer.lock`, `phpunit.xml`, `vite.config.js`). Se terminó de mover todo a `ARCHIVOS/laravel-legacy-julio/`, y de paso se archivaron (sin borrar, todo recuperable en `ARCHIVOS/` y en el historial de git) ~23 `.md` sueltos de sesiones viejas y 8 scripts de deploy duplicados/abandonados. Ver sección **"Estructura del repo (2026-09-04)"** más abajo.

**Sobre lo que se creía "sin publicar":** el 2026-09-03 se dio por hecho que el ojito PWA + sesión persistente + botón WhatsApp seguían sin deployar a Cloudflare Pages. Era un error — Hugo las vio funcionando en producción el 2026-09-03/04 y, al verificar en vivo, se confirmó: el hash del bundle JS servido en `sertecapp.pendziuch.com` coincide exactamente con el build local hecho esa tarde. **Lección: no asumir estado de deploy sin comparar contra lo que corre en vivo (hash de build, screenshot, etc.) — ni aunque la sesión anterior lo haya documentado como pendiente.**

Ver reglas 6, 7 y 8 arriba — nacieron de este incidente.

---

## Estado actual verificado (2026-09-04) ✅

**⚠️ IMPORTANTE — qué es "producción" hoy y qué rama la alimenta:**

`demo.pendziuch.com` (Hostinger) y `sertecapp.pendziuch.com` (PWA) son hoy el entorno de **demo/staging para Luis**, todavía NO el dominio de producción final — eso es intencional, es donde se sigue iterando. Cuando haya un dominio de producción real separado, **`main` se va a usar para ese deploy**. Hasta entonces:

- **`development` es la rama que manda.** El webhook de GitHub dispara `deploy-sertecapp.sh` en Hostinger en cada push a `development` (confirmado leyendo el script en el servidor — usa `git archive origin/development backend-laravel/`, ver sección de deploy más abajo). Todo lo que está en `demo.pendziuch.com` hoy viene de `development`, no de `main`.
- **`main` es solo un espejo de referencia** que se sincroniza manualmente (fast-forward) cuando `development` está estable, para no perder de vista qué es "lo último confirmado andando". No dispara ningún deploy por sí sola todavía.
- La PWA (`sertecapp-tecnicos/`) se deploya aparte, pero **desde 2026-09-04 es automático**: Cloudflare Pages (proyecto `sertecapp-live`) está conectado directo al repo de GitHub, build+deploy en cada push a `development`. Ver detalle en "Deploy automático de la PWA (Cloudflare Pages)" más abajo.

**Dos frontends PWA — a propósito, no es un error:**

| Frontend | URL | API a la que apunta | Rol |
|---|---|---|---|
| **Activo (actualizar siempre acá)** | [https://sertecapp.pendziuch.com](https://sertecapp.pendziuch.com) | `https://demo.pendziuch.com` (Hostinger, MySQL) | El que se sigue desarrollando |
| Backup de demo (congelado a propósito) | [https://sertecapp-tecnicos.pages.dev](https://sertecapp-tecnicos.pages.dev) | `https://sertecapp-worker.pendziuch.workers.dev` (Worker viejo + D1/SQLite) | No tocar ni preocuparse si queda "atrás" |

| Entorno | URL | Estado |
|---------|-----|--------|
| Admin panel (Hostinger) | [https://demo.pendziuch.com/sertecapp/login](https://demo.pendziuch.com/sertecapp/login) | ✅ Funciona, al día con `development` (deploy webhook confirmado 2026-09-03 22:09) |
| API REST (Hostinger) | [https://demo.pendziuch.com/api/v1](https://demo.pendziuch.com/api/v1) | ✅ Funciona |
| PWA técnicos (Cloudflare Pages) | [https://sertecapp.pendziuch.com](https://sertecapp.pendziuch.com) | ✅ Al día — ojito mostrar/ocultar contraseña, sesión persistente y botón WhatsApp confirmados en vivo el 2026-09-04 (hash de bundle JS coincide con el build local del 2026-09-03) |
| Admin panel (local) | [http://localhost:8000/sertecapp/login](http://localhost:8000/sertecapp/login) | Verificar si está levantado (`netstat`) antes de asumir |

**Git:** `main` y `development` sincronizados en el mismo commit desde el 2026-09-04 (verificar con `git log -1` en cada uno — no asumir que están desalineados).

**Login producción (demo):** `pendziuch@gmail.com` / `SerTecApp2026!`

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

### Cómo funciona el deploy realmente (verificado 2026-09-04)

`deploy-sertecapp.sh` (vive en `~/deploy-sertecapp.sh` en Hostinger, y hay copia versionada en la raíz del repo) usa **`git archive` + `tar --strip-components=1`**, NO `git pull`:

```bash
git fetch origin development
git archive origin/development backend-laravel/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1
```

Esto extrae el contenido exacto del subárbol `backend-laravel/` del commit de `development` directamente sobre `public_html/backend-laravel/`, sin importar si el working tree local del servidor está sucio o desactualizado (lo está — el `.git` interno de esa carpeta en Hostinger quedó con un HEAD viejo de julio y no se usa para nada, es ruido inofensivo). El `.env` se respalda y restaura aparte. Esto es lo que resolvió, de hecho, el viejo problema de "dos paths distintos" (ver más abajo) — aunque nunca se documentó como resuelto hasta ahora.

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
| `development` | Trabajo activo — **siempre trabajar acá** — es la que dispara el deploy real a `demo.pendziuch.com` vía webhook |
| `main` | Espejo manual de referencia hoy. Reservada para cuando exista un dominio de producción real separado de la demo (ver "Estado actual verificado" arriba) |

Flujo: trabajar en `development` → push → auto-deploy a Hostinger vía webhook. Sincronizar `main` de vez en cuando con fast-forward cuando `development` esté estable (no dispara nada, es solo referencia).

---

## Auto-deploy — YA FUNCIONA (confirmado 2026-09-04, no es "pendiente")

El webhook de GitHub → `deploy.php` → `deploy-sertecapp.sh` está activo y confirmado corriendo (log de deploy con timestamp real tras un push a `development`). Ver "Cómo funciona el deploy realmente" arriba para el mecanismo exacto (`git archive`, no `git pull`). Si en el futuro parece que dejó de andar, chequear primero `tail -f /tmp/sertecapp_deploy.log` en el servidor antes de asumir que hay que reconfigurar nada.

---

## ⚠️ Regla agregada 2026-07-28

**NUNCA hardcodear paths del panel Filament** (ej: `/sertecapp/`, `/admin/`).
Siempre usar `NombreResource::getUrl('index')` en widgets y cualquier link interno.
Razón: si cambia el nombre del panel o el dominio, los links se rompen.
Corrección aplicada en `StatsOverviewWidget.php` — commit `0e5e352`.

---

## Estado actualizado 2026-07-28 (histórico — mecanismo de deploy superado, ver "Cómo funciona el deploy realmente" arriba)

### Deploy funcionando (en ese momento)
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

## Problema histórico de paths duplicados — RESUELTO (ver "Cómo funciona el deploy" arriba)

Durante julio-agosto 2026 esto fue un problema real: local trackea `backend-laravel/app/Filament/...` (repo root = `SerTecApp/`) pero Hostinger necesitaba `app/Filament/...` (repo root = `backend-laravel/`), y usar `git pull` ahí nunca actualizaba nada bien. Se probaron soluciones manuales (robocopy antes de cada commit, ver historial de este archivo si hace falta el detalle) que nunca quedaron del todo prolijas.

**Se resolvió de fondo, sin que nadie lo documentara como tal, con `deploy-sertecapp.sh`:** usa `git archive origin/development backend-laravel/ | tar --strip-components=1`, que extrae directamente el subárbol correcto sin depender de que las rutas del repo local y del servidor coincidan. Ver detalle completo en "Cómo funciona el deploy realmente" más arriba. **No hace falta robocopy ni sincronizar paths a mano — ya no es necesario.**

Pendiente real que queda (no es un blocker, es trabajo de UI): traducir al español los títulos de recursos Filament que faltan — WorkPart, Customer, Equipment, Part, Visit, User.

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

---

## Sesión 2026-09-04 — orden y sincronización (sin tocar features)

Objetivo de Hugo: "avanzar sin romper nada" y que el repo "se vea 100% pro". No se tocó ninguna feature ni código funcional — solo prolijidad y corrección de documentación desactualizada.

### Verificado en vivo antes de tocar nada (regla 0)
- Ojito, sesión persistente y botón WhatsApp: confirmados en producción (screenshot + hash de bundle JS coincidente). CLAUDE.md decía lo contrario — corregido.
- Deploy a Hostinger: leído `deploy-sertecapp.sh` directo en el servidor por SSH. Usa `git archive --strip-components=1`, no `git pull`. El viejo "problema de paths duplicados" está resuelto de hecho desde hace rato, solo nunca se documentó.
- `development` es la rama que dispara el deploy real (vía webhook). `main` es un espejo manual — Hugo aclaró que la va a reservar para un futuro dominio de producción separado, distinto de `demo.pendziuch.com`.

### Hecho
1. `sertecapp-tecnicos/public/sw.js`, `workbox-*.js`, `fallback-*.js` dejaron de versionarse (se regeneran en cada build, ensuciaban el diff sin ser código real) — commit `c39d060`.
2. `main` sincronizada con `development` vía fast-forward (11 commits, todo ya en producción) — commit `c39d060`, pusheado a GitHub.
3. Completado el archivado del esqueleto Laravel legacy que había quedado a medias en la raíz (`bootstrap/`, `public/`, `storage/`, `tests/`, `artisan`, `composer.json`, `composer.lock`, `phpunit.xml`, `vite.config.js`) → `ARCHIVOS/laravel-legacy-julio/` — commit `1b1cd60`.
4. Archivados ~23 `.md` históricos y 8 scripts de deploy/debug obsoletos → `ARCHIVOS/docs-historicos/` y `ARCHIVOS/scripts-viejos/` — commit `03a9c0f`. Nada se borró.
5. Este archivo reescrito para reflejar el estado real verificado hoy (ver secciones "Estado actual verificado", "Cómo funciona el deploy realmente", "Estructura del repo").

### Backlog explícito (no se tocó hoy, a propósito)
- Definir y configurar el dominio de producción real que usará `main` (hoy no existe todavía, todo corre sobre `demo.pendziuch.com`).

### Verificado más tarde el mismo día: traducciones OK, jerarquía de superadmin tenía una falla de seguridad real (CORREGIDA)

Hugo pidió reverificar dos ítems que este mismo archivo había dejado como backlog pendiente:

- **Traducción de títulos de Filament: confirmado que SÍ estaba hecha.** Todos los resources (`WorkPart`, `Customer`, `Equipment`, `Part`, `Visit`, `User`, `WorkOrder`, `Budget`, `Subscription`, `WorkshopItem`) tienen `$navigationLabel`/`$modelLabel`/`$pluralModelLabel` en español. Este archivo estaba desactualizado en ese punto también.
- **Jerarquía de superadmin: NO estaba hecha — era una falla de seguridad real, corregida en el commit `48931df`.** En `UserResource.php`, el dropdown de "Rol" ofrecía **todos** los roles (incluido `super_admin`) a cualquier usuario que no fuera `supervisor` — es decir, cualquier `administrador` podía autoasignarse `super_admin`, o resetear la contraseña de una cuenta `super_admin` existente vía las acciones "Enviar Acceso"/WhatsApp/Email (esas acciones no chequeaban el rol del registro destino).

**Fix aplicado (3 capas, en `backend-laravel/app/Filament/Resources/UserResource.php`):**
1. `options()` del select de rol excluye `super_admin` salvo que el usuario logueado ya sea `super_admin`.
2. Regla de validación server-side (`->rule(...)`) en el mismo campo — no depende solo del dropdown, rechaza cualquier submit armado a mano que intente colar `super_admin`.
3. Helper `isProtectedSuperAdmin()` usado en `canEdit()` y en las 3 acciones de reset de password/envío de acceso — bloquea que un no-`super_admin` edite o resetee la password de una cuenta `super_admin` existente.

Nombre del rol verificado contra la DB real de producción por SSH antes de escribir el fix (7 roles: `admin`, `administrador`, `cliente`, `customer_viewer`, `super_admin`, `supervisor`, `técnico` — **ojo:** la `.env` local por defecto apunta a SQLite con datos de prueba desactualizados y distintos nombres de rol; para verificar roles/permisos siempre chequear contra Hostinger, no contra local, salvo que se fuerce `.env.mysql.local`).

**Lección para la próxima sesión:** cuando Hugo dice "creo que ya hicimos X", puede tener razón en una cosa y no en otra dentro del mismo pedido — verificar cada ítem por separado en el código real, no asumir que todo el lote está en el mismo estado.

### Cabos sueltos detectados el 2026-09-03 — estado al 2026-09-04
- `backend-laravel/deploy.bat` (vacío) y `backend-laravel/fix_wps.py` (script roto, `WorkPartService.php` nunca existió) — **resuelto:** archivados en `ARCHIVOS/scripts-viejos/` el 2026-09-04, no borrados.
- **Duplicación `app/Filament/` (raíz) vs `backend-laravel/app/Filament/`** — **resuelto:** el `app/Filament/` de la raíz era parte del mismo esqueleto legacy, ya archivado completo en `ARCHIVOS/laravel-legacy-julio/` el 2026-09-04. Hoy solo existe `backend-laravel/app/Filament/`, sin duplicados. El deploy a Hostinger nunca dependió de esta carpeta raíz — usa `git archive` sobre `backend-laravel/` (ver "Cómo funciona el deploy realmente").
- ~25 `.md` sueltos en la raíz — **resuelto:** archivados en `ARCHIVOS/docs-historicos/` el 2026-09-04. Ver sección "Estructura del repo" abajo.

## Estructura del repo (2026-09-04)

Raíz limpia: `CLAUDE.md`, `README.md`, `ARCHITECTURE.md`, `deploy-sertecapp.sh` (el único script de deploy vigente, corre vía cron/webhook en Hostinger), `deploy.bat` (helper local: commit + push a `development`), `test-sertecapp.bat` (chequeo rápido de infra), más las carpetas de código (`backend-laravel/`, `sertecapp-tecnicos/`, `sertecapp-worker/`) y `ARCHIVOS/`.

`ARCHIVOS/` — todo lo archivado, **nada borrado, todo recuperable** (está en git, se puede traer de vuelta con `git mv` si algo hiciera falta):
- `laravel-legacy-julio/` — el esqueleto Laravel duplicado que quedó suelto en la raíz de una migración vieja (código muerto, sin `vendor/` ni `.env`, nunca lo usó ningún deploy). Completo desde 2026-09-04.
- `docs-historicos/` — ~23 `.md` de sesiones de deploy/planificación anteriores a este archivo. Si hace falta contexto de algo viejo, buscar ahí antes que preguntar.
- `scripts-viejos/` — deploy scripts duplicados/abandonados, scripts de debug/migración de un solo uso, logs sueltos.

**Regla para el futuro: si algo en `ARCHIVOS/` parece hacer falta de nuevo, moverlo de vuelta con `git mv` (no copiar) para no volver a duplicar.**

---

## Sesión 2026-09-04 (continuación) — auditoría de seguridad completa + fixes

Motivo: antes de pensar en un dominio de producción real o vender esto como Core, había que estar seguros de que no es explotable. Auditoría de solo lectura primero (3 agentes en paralelo: backend/Filament, API REST, PWA), 16 hallazgos, 5 críticos explotables sin cuenta. Plan aprobado por Hugo, ejecutado en 3 fases, todo commiteado y deployado a `demo.pendziuch.com` el mismo día.

### Fase A — accesos públicos/indebidos puntuales (commit `6d1dea8`)
- `magic-link/generate` y los 3 endpoints de técnico (`ordenes/tecnico/{tecnico}`, `partes/{workOrderId}`, `POST partes`) pasaron a requerir `auth:sanctum` — antes eran **100% públicos** (el código tenía el comentario literal "SIN AUTH para testing"). Cualquiera sin cuenta podía leer PII de clientes/firmas o forjar el cierre de una orden.
- `ConfiguracionEmail`/`ConfiguracionGeneral`: `canAccess()` ahora protege la ruta real (antes solo ocultaban el link del menú — un técnico podía entrar directo por URL y ver/cambiar las credenciales SMTP).
- `budgets/{budget}/pdf`: agrega chequeo de rol admin-tier.
- `UserController` (API): `index`/`show`/`destroy` sin ningún chequeo antes — cualquier técnico podía listar todo el staff o borrar cualquier cuenta.
- `WorkPartResource` + `ViewWorkPart`: un técnico podía autoaprobar su propio parte (las acciones Aprobar/Rechazar solo miraban el estado, no el rol).
- `cors.php`: acotado el wildcard `*.pages.dev` (dominio compartido gratuito) al subdominio propio.

### Fase B — IDOR sistémico en la API REST (commit `71250cc`, el hallazgo más grave)
Las Policies de Filament (`app/Policies/*.php`) usaban nombres de permiso estilo Shield (`view_any_work::order`) que **no existen** en la tabla de permisos, y los controllers ni siquiera las consultaban. En la práctica: cualquier token Sanctum autenticado, incluido el de un técnico, podía ver/editar/borrar órdenes, clientes, visitas, presupuestos, suscripciones, taller y repuestos de cualquiera.

Reescritas las 8 Policies (`WorkOrder`, `Visit`, `WorkshopItem`, `Customer`, `Part`, `Equipment`, `Budget`, `Subscription`) con el mismo patrón `hasAnyRole()` que ya usa el resto del código:
- `administrador`/`super_admin`/`supervisor`: acceso total, **cero cambio de comportamiento**.
- `técnico` en modelos con `assigned_tech_id` (WorkOrder/Visit/Workshop): solo sus propios registros, nunca borra vía API.
- `técnico` en modelos sin dueño (Customer/Part/Equipment/Budget): solo lectura, igual que ya usaba.
- `técnico`: sin acceso a Subscription (no es parte de su flujo).

Cada controller agrega `authorizeResource()` en el constructor. `index()` de WorkOrder/Visit/Workshop scopea por `assigned_tech_id` para técnico. Verificado con datos reales de producción vía tinker (no solo lectura de código): técnico puede actualizar su propia orden, NO puede tocar la de otro, NO puede borrar, SÍ puede ver clientes, NO puede editarlos; admin mantiene acceso total intacto.

**Efecto colateral encontrado y corregido de paso:** casi todos los `FormRequest` (`Store/UpdateCustomerRequest`, `Store/UpdateEquipmentRequest`, `StorePartRequest`, `StoreVisitRequest`, `Store/UpdateWorkOrderRequest`) llamaban a permisos Spatie (`customers.create`, etc.) nunca asignados a los roles reales — esos endpoints estaban bloqueados para todo el mundo, incluido `super_admin`, si alguna vez se llegaban a usar. Se corrigieron para delegar a las Policies nuevas.

**También se cerró en `StoreUserRequest`/`UpdateUserRequest` el mismo hueco de auto-escalación a `super_admin`** corregido más temprano en Filament (ver sección arriba), pero que seguía abierto por la API directa — el array `roles` no validaba quién podía asignar `super_admin`.

### Fase C — hardening adicional (commit `2b0f898`)
- `User` model: hook `booted()` que revoca todos los tokens Sanctum cuando `is_active` pasa a `false` (cubre tanto Filament como la API). Antes un técnico desactivado seguía con acceso hasta 365 días.
- PWA: borrado `app/l/AutoLoginContent.tsx` (código muerto, decodificaba `email:password` en Base64 desde la URL).
- PWA: sacado un `console.log` que volcaba el perfil completo del usuario en `/ordenes`.
- PWA: corregido un typo de dominio en `next.config.ts` (el `runtimeCaching` del Service Worker apuntaba a `sertecapp.pendziuch.com` en vez de `demo.pendziuch.com` — era un no-op, quedó bien apuntado) + purga de Cache Storage agregada al logout/"Limpiar Caché" en `ordenes` y `admin`.

Los cambios de PWA (Fase C) se deployaron el mismo día: primero a mano con `wrangler pages deploy` (verificado en el navegador, sin errores de consola), y después se automatizó el pipeline entero — ver sección siguiente.

## Deploy automático de la PWA (Cloudflare Pages) — configurado 2026-09-04

`sertecapp.pendziuch.com` corre en el proyecto de Cloudflare Pages **`sertecapp-live`** (¡ojo! no es `sertecapp-tecnicos` — ese es el proyecto del frontend viejo/congelado, ver tabla de "Estado actual verificado"). Antes de hoy el deploy era 100% manual: build local (`NEXT_EXPORT=1 npx next build --webpack`) + `wrangler pages deploy out --project-name=sertecapp-live`.

**Ahora es automático**, igual que Hostinger pero por integración nativa de Cloudflare (no webhook + script propio): se conectó el repo `PENDZIUCH/SerTecApp` directo desde el dashboard de Cloudflare (Workers y Pages → `sertecapp-live` → Configuración → Desarrollo → Repositorio Git → Conectar). Cada push a `development` dispara un build y deploy solo, sin tocar nada.

**Configuración de build (Cloudflare dashboard, sección "Desarrollo" de `sertecapp-live`):**

| Campo | Valor |
|---|---|
| Rama de producción | `development` |
| Directorio raíz | `sertecapp-tecnicos` |
| Comando de compilación | `npx next build --webpack` |
| Resultado de compilación | `out` |
| Rutas de vigilancia de compilación | `sertecapp-tecnicos/*` (acotado el 2026-09-04 — antes era `*`, disparaba rebuild hasta con un push que solo tocaba `backend-laravel/` o este mismo `CLAUDE.md`) |

**Variables de entorno de build** (sección "Variables y secretos" del mismo proyecto):

| Variable | Valor | Por qué |
|---|---|---|
| `NEXT_EXPORT` | `1` | Activa en `next.config.ts` el modo de export estático + PWA — sin esto hace un build normal que no sirve para Cloudflare Pages (sin `out/`, sin `sw.js`). |
| `NODE_VERSION` | `20` | Sin fijarla, Cloudflare puede usar una versión vieja por defecto. |

`--webpack` en el build command es obligatorio: Next.js 16 usa Turbopack por defecto y `next-pwa` (el plugin que genera el service worker) todavía no lo soporta — sin el flag, el build tira error.

**Gotcha real que pasó al conectar:** el primer build automático se disparó apenas se conectó el repo, ANTES de que las variables de entorno quedaran guardadas — resultado: deployó una versión sin `NEXT_EXPORT`, sin export estático, con `sw.js` vacío (0 bytes) en producción. Se corrigió con un "Reintentar implementación" manual desde la pestaña Implementaciones una vez confirmadas las variables. **Si en el futuro se reconecta el repo o se cambia esta config, verificar el primer build resultante antes de asumir que quedó bien** — chequear que `https://sertecapp.pendziuch.com/sw.js` no esté vacío y contenga `demo\.pendziuch\.com` (con las barras de escape, es un regex minificado).

**Costos:** plan gratis de Cloudflare Pages, 500 builds/mes, sin tarjeta de crédito y sin cobro automático si se excede (simplemente no te deja hacer más builds hasta el mes siguiente o hasta upgradear a mano). Con la frecuencia de pushes de este proyecto, no hay riesgo real de acercarse a ese límite.

**Vuelta atrás:** Cloudflare Pages guarda el historial completo de deploys (pestaña "Implementaciones") — cualquiera se puede volver a promover a producción con un clic, sin necesidad de revertir el commit.

### Verificado después de cada fase
`demo.pendziuch.com` (login admin, API health, login API) y `sertecapp.pendziuch.com` siguen respondiendo 200 tras las 3 fases. Los 4 endpoints que pasaron a requerir auth devuelven 401 sin token. La lógica de Policies se probó contra datos reales de producción (técnico real, orden propia/ajena) vía tinker de solo lectura, sin dejar residuos.

### Explícitamente fuera de esta sesión (documentado, backlog real)
- **Rediseño completo Roles↔Permisos: RESUELTO más tarde el mismo día** — ver sección "Unificación Roles↔Permisos" más abajo.
- **Magic link de larga vida (30-365 días) viajando en texto plano por WhatsApp/email** — **RESUELTO el 2026-09-06**, ver sección "Magic link de un solo uso" más abajo.
- PDF de presupuestos: ya resuelto (ver sección de esa misma tarde más abajo).

## Convergencia con core/v1 — hecha 2026-09-04 (commit `587c746`)

**No se mergeó la rama `core/v1` completa.** Verifiqué el diff real antes de asumirlo: esa rama diverge de agosto, antes de semanas de trabajo y de toda la auditoría de seguridad de hoy — mergearla entera habría reintroducido el magic-link público, el IDOR sistémico y la auto-escalación a `super_admin` que se cerraron hoy mismo, porque `core/v1` tiene versiones viejas de `UserResource`, `TechnicianController`, `WorkPartResource`, las 8 Policies, los Controllers de la API y los FormRequests.

Lo que sí se trajo, aislado línea por línea (no archivo completo):
- **`app/Services/ModuleManager.php`** (nuevo) — sistema de módulos on/off por instancia, leyendo un `SystemSetting` `active_modules`. Default: todo activo si la key no existe (confirmado contra producción antes de portarlo: no existía, cero impacto).
- **`app/Filament/Pages/ConfiguracionModulos.php`** (nueva página, menú "Módulos", solo `super_admin`) — togglea 8 módulos: Clientes, Órdenes+Partes, Visitas, Presupuestos, Suscripciones, Stock/Repuestos, Taller, Equipamiento.
- **`canAccess()` agregado a 9 Resources** (Budget, Customer, Equipment, Part, Subscription, Visit, WorkOrder, WorkPart, Workshop) — cada uno consulta `ModuleManager::isActive('clave')`. Nada más de esos archivos se tocó (ni `canViewAny`, ni `canEdit`, ni el scoping por técnico de la auditoría de hoy).
- `config/app.php`/`.env.example`: `pwa_url`/`PWA_URL` registrado formalmente (ya se usaba antes vía default inline).
- `install/` (script + `.env.template` + README) y `MODULES.md` en la raíz: para cuando exista un segundo cliente — no se ejecutan sobre la instancia de Fitness Company.

**Bug de seguridad encontrado y corregido al portar (mismo patrón de hoy):** `ConfiguracionModulos.php` en `core/v1` solo tenía `shouldRegisterNavigation()`, sin `canAccess()` — cualquier usuario autenticado podría haber entrado por URL directa y apagado módulos completos de la instalación. Se le agregó `canAccess()` antes de traerla. De paso encontré el mismo hueco ya en `development`, sin relación con `core/v1`, en `PdfTemplateResource` y `SystemLogResource`/`SystemSettingResource` (estas dos últimas al menos tenían `shouldRegisterNavigation()`, `PdfTemplateResource` no tenía ninguna protección) — las tres corregidas y con labels en español agregados de paso.

**Verificado contra producción (tinker de solo lectura):** con la key `active_modules` sin existir, los 9 `canAccess()` devuelven `true` para un técnico real (cero cambio visible hoy); `ConfiguracionModulos`/`PdfTemplateResource`/`SystemLogResource`/`SystemSettingResource` devuelven `false` para el técnico y `true` para `hugo` (super_admin). Deploy a Hostinger confirmado, `demo.pendziuch.com` respondiendo 200.

**Pendiente, a decidir con Hugo:** borrar la rama `core/v1` (local y remota) ahora que lo útil ya está en `development` — evita que alguien la mergee entera por error en el futuro. No se borró todavía, queda para la próxima vez que se toque este tema.

## Cobertura de tests agregada (Hugo dejó trabajando solo, 2026-09-04 tarde)

Con Hugo afuera, se dedicó tiempo a convertir en tests automáticos lo que hasta ahora se verificaba a mano con scripts de tinker por SSH (caro en tokens, hay que rehacerlo cada vez) — idea del propio Hugo: "armá tests que chequeen cosas que te hacen consumir tokens".

- **`backend-laravel/tests/Feature/SecurityPoliciesTest.php`, `ModuleManagerTest.php`, `UserEscalationGuardTest.php`, `PublicEndpointsClosedTest.php`, `NotificationScopingTest.php`, `BudgetPdfRouteTest.php`** — 24 tests / 154 aserciones, corren contra sqlite en memoria (`RefreshDatabase`), nada toca datos reales. Correrlos con `php artisan test tests/Feature/NombreDelTest.php` (uno por uno, o los 6 juntos en el mismo comando) — **NO** con `php artisan test` a secas (ver punto siguiente).
- **⚠️ Hallazgo de paso: el test suite viejo (`CustomerImportExportTest`, `EquipmentTest`, `WorkOrderTest`) está roto desde antes de hoy.** Usa sintaxis de Pest (`beforeEach()`, etc.) pero `pestphp/pest` nunca se agregó como dependencia real a `composer.json` — solo aparece como sugerencia transitiva de otros paquetes en `composer.lock`, `vendor/bin/pest` no existe. Por eso `php artisan test` sin argumentos falla apenas llega a `EquipmentTest.php`. No se tocó — instalar Pest es una decisión de dependencias que le toca decidir a Hugo (`composer require pestphp/pest pestphp/pest-plugin-laravel --dev` sería el fix), no algo para resolver sin que esté.
- **`test-sertecapp.bat`** (raíz) ampliado con los 4 chequeos de "debe dar 401 sin token" + el chequeo del dominio correcto en `sw.js` — antes estos se verificaban con curls sueltos armados a mano en cada sesión. Correrlo entero lleva unos segundos y dice de una si algo de la auditoría de hoy se rompió.

### Barrido completo del panel Filament (mismo patrón de vulnerabilidad, sistemático)
Se listaron los 14 Resources + 3 Pages del panel y se verificó que TODOS tuvieran algún control de acceso real (no solo `shouldRegisterNavigation`). Encontrado un caso más: **`NotificationResource` no tenía ninguna protección** — cualquier usuario autenticado que navegara a `/sertecapp/notifications` veía las notificaciones de todos los usuarios y podía crear notificaciones a nombre de cualquiera. Corregido con `getEloquentQuery()` scoping + `canCreate()`/`canDelete()` restringidos (commit `21c68cf`).

**Hallazgo de paso, más de fondo:** `App\Models\Notification` (el modelo detrás de ese Resource) **no coincidía con el esquema real de la tabla `notifications`** — la tabla es la estándar de Laravel (`notifiable_type`/`notifiable_id`/`data` JSON), no las columnas `user_id`/`title`/`message` que el modelo declaraba. Sin ninguna otra referencia en el codebase. Es decir: era código muerto que nunca pudo funcionar — la campanita de notificaciones real usa el sistema nativo de Filament (`->databaseNotifications()` en `AdminPanelProvider.php`, confirmado) sobre la tabla estándar. **Borrado con confirmación de Hugo** (`NotificationResource` + sus 3 Pages + `App\Models\Notification` + `NotificationPolicy`) el mismo día, junto con la unificación de Roles↔Permisos — ver sección de abajo.

### Bug de UX corregido de paso (no era hueco de seguridad)
`routes/web.php` — `budgets/{budget}/pdf` daba 500 en vez de un redirect prolijo para un usuario sin sesión (`middleware('auth')` intentaba resolver una ruta nombrada `'login'` que no existe en esta app, solo existe `filament.sertecapp.auth.login`). Ya bloqueaba el acceso igual, solo con mal manejo de error. Corregido con un chequeo manual que redirige a la pantalla de login real (commit `fb6bfa1`). Nota: no había ningún presupuesto real en producción para probarlo en vivo (`Budget::count() === 0` hoy) — validado con test local (factory) en los 3 casos: anónimo, técnico, admin-tier.

## Unificación Roles↔Permisos — hecha 2026-09-04 (commit `ddf357e`)

**Motivo:** Hugo estuvo probando el panel de Roles a mano (tildando permisos para `administrador`) y notó efecto real — contradecía lo que se le había dicho ("el panel es decorativo"). Se volvió a verificar contra producción y la realidad era una **mezcla inconsistente, no un sistema roto**: las 9 Policies reescritas en la auditoría de la mañana usaban `hasAnyRole()` hardcodeado (decorativo a propósito, por seguridad), pero todo lo demás que no tiene `canViewAny`/`canEdit` propios (ej. `NotificationResource`) cae al default de Filament, que sí consulta la Policy real — ahí el panel de Roles mandaba de verdad. Confirmado con `supervisor->can('viewAny', Notification::class)` = `true`.

**Estado de permisos encontrado en producción antes de tocar nada** (226 permisos, dos esquemas mezclados — `customers.view` de un seeder viejo en inglés, y `view_any_customer`/`create_work::order` que genera Filament Shield):
- `super_admin`: 226 (todos).
- `supervisor`: 120, CRUD completo estilo Shield en Budget/Customer/Notification/Part/Subscription/User/Visit/WorkOrder/WorkPart/Workshop (le faltaba Equipment) — alguien lo configuró a mano en algún momento, no fue tocado por ninguna sesión de Claude.
- `administrador`: 12 (solo Customer, recién tildado por Hugo) — antes tenía 0.
- `técnico`: 18, esquema dot-notation viejo, mapeo correcto de lo que ya hace.

**Qué se hizo:**
1. `Gate::before` en `AppServiceProvider::boot()` — `super_admin` pasa cualquier chequeo sin depender de tener los 226 permisos bien sincronizados.
2. `database/seeders/SyncShieldPermissionsSeeder.php` (nuevo, **solo aditivo** — `givePermissionTo()`, nunca `syncPermissions()`, no pisa nada asignado a mano): le da a `administrador`/`supervisor` el CRUD completo estilo Shield en los 9 recursos de dominio + Usuarios (lo que le faltaba a cada uno), y a `técnico` los permisos Shield equivalentes a lo que ya hacía (view+create+update en órdenes/partes/visitas/taller, solo view en clientes/repuestos/equipos/presupuestos, nada en suscripciones/usuarios). **Ya se corrió contra producción** (`php artisan db:seed --class=SyncShieldPermissionsSeeder --force`) — administrador quedó con 120 permisos propios de rol (170 totales porque ese usuario específico también tiene asignado el rol legacy `admin`, no relacionado a este cambio), supervisor con 132, técnico con 42.
3. Las 9 Policies + `UserResource`/`StoreUserRequest`/`UpdateUserRequest`/`UserController` migradas de `hasAnyRole()` a permisos reales (`update_work::order`, `create_user`, etc.), **manteniendo** el scoping por `assigned_tech_id`/`technician_id` para técnico y la regla de anti-escalación a `super_admin` (`isProtectedSuperAdmin`) sin tocar — esas siguen corriendo siempre, no son negociables vía el panel de Roles.
4. Borrado `NotificationResource`/`Model`/`Policy` (código muerto, ver arriba).

**Verificado contra producción después de correr el seeder** (tinker, con datos reales): administrador puede actualizar/borrar work orders y gestionar usuarios; técnico puede actualizar su propia orden, NO puede borrarla, NO puede tocar una orden ajena, puede ver clientes pero no editarlos, no ve suscripciones; `super_admin` pasa todo vía `Gate::before` sin depender de sus permisos explícitos. 25 tests locales (157 aserciones) — incluyen 2 casos nuevos que antes eran imposibles de probar: que sacarle un permiso a un rol le saca el acceso de verdad, y que `super_admin` funciona aunque no tenga permisos asignados.

**Resultado para Hugo:** ahora si destildás algo en el panel de Roles para `administrador`/`supervisor`/`técnico`, tiene efecto real en todo el panel — consistente en todos lados, no una mezcla.

## Magic link de un solo uso — hecho 2026-09-06 (commit `a78953d`)

**Motivo:** el magic link (acceso de un clic vía WhatsApp/email) quedó identificado en la auditoría del 2026-09-04 como el único hallazgo de seguridad sin cerrar: era un token Sanctum de acceso total (`['*']`), válido **365 días** (no 30-365 como decía este archivo antes de verificarlo — los 30 días eran de un endpoint de API que ni se usa desde el frontend, código muerto), viajando en texto plano por WhatsApp/email, y reusable indefinidamente durante todo ese año.

**Lo que se verificó antes de tocar nada:** el link no era solo la puerta de entrada — era la sesión completa. El front (`sertecapp-tecnicos/app/l/page.tsx`) guardaba ese mismo token en `localStorage` y lo seguía usando como credencial de la app, es lo que sostiene el "Hola Juan" de la sesión persistente. Por eso simplemente borrar el token al primer uso hubiera roto esa feature.

**Fix aplicado:**
1. El link ahora vence en **24hs** si no se usa (decisión de Hugo, entre 1h/24h/7 días — eligió 24h por margen práctico sin dejar una ventana larga de exposición).
2. Al primer clic, `MagicLinkController::verify()` canjea el magic link por un token de sesión nuevo (mismo tipo que un login normal, sin vencimiento — preserva la sesión persistente) y **destruye el magic link en el momento** (`$token->delete()`).
3. Si el mismo link se vuelve a abrir después (reenviado, interceptado, o alguien vuelve a tocarlo): 401, ya no sirve.
4. Se unificó la creación del link en `MagicLinkController::issueLinkFor()` — antes había 4 copias del mismo código (`UserResource.php` ×3 + `MagicLinkController::generate` ×1) con expiraciones distintas entre sí (365 días vs. 30 días), una inconsistencia que ya no existe.

**Tests nuevos:** `backend-laravel/tests/Feature/MagicLinkSingleUseTest.php` — 4 tests que confirman el vencimiento de 24hs, el canje por token de sesión, que reusar el mismo link falla, y que el token de sesión resultante no hereda el vencimiento corto del link. Nota interna: probar "dos requests seguidos con el mismo token" en un test de Laravel requiere `Auth::forgetGuards()` entre medio — Sanctum memoiza el guard resuelto en el primer request dentro del mismo método de test, así que sin eso el segundo request da un falso 200 (no pasa en producción, ahí cada request HTTP es un proceso nuevo).

**Verificado:** 29 tests / 167 aserciones en verde (toda la suite de seguridad + los 4 nuevos) antes de pushear. Deploy automático a Hostinger + Cloudflare Pages disparado por el push a `development`.

## Política de tests — criterio acordado 2026-09-06

Hugo preguntó si toca escribir test para cada cosa que se agrega de acá en adelante. Criterio acordado, no es "sí a todo" ni "no a nada":

- **Sí, casi siempre:** lógica de seguridad/permisos (quién puede hacer qué), y cualquier bug real que ya se rompió una vez (evita que vuelva sin darnos cuenta). Es barato de escribir y es exactamente lo que evita gastar tokens re-verificando lo mismo a mano por SSH/tinker en cada sesión — el motivo por el que existe esta suite.
- **No hace falta:** cambios de texto/UI, ajustes de estilo, configuración de infraestructura (deploy, CORS, envs) — ahí lo que vale es la verificación en vivo (curl, `test-sertecapp.bat`, mirarlo en el navegador), no un test unitario.
- **Depende:** lógica de negocio nueva con reglas no triviales (ej. algo como el magic link, con una ventana de tiempo y un estado que cambia) — ahí sí vale la pena, porque es fácil romperlo sin darse cuenta en un cambio futuro y no es algo que se vea a simple vista mirando el panel.

En la práctica: cada vez que se toque algo de auth/roles/tokens/dinero (presupuestos, suscripciones), se agrega test en el mismo commit. El resto se evalúa caso a caso, sin convertir esto en burocracia.

## Preparación para cambio de dominio — auditado 2026-09-07 (commit `7fb87c0`)

Hugo preguntó (a raíz de evaluar meter el admin de Filament bajo un subdirectorio de `sertecapp.pendziuch.com` — **descartado**, son dos plataformas distintas — Cloudflare Pages no ejecuta PHP; si se quiere un dominio unificado más adelante, la vía simple es un subdominio con CNAME a Hostinger, no un proxy) si el sistema está preparado para cambiar de dominio/subdominio sin problemas. Auditoría real del código (no de memoria):

- **Bien:** `APP_URL`/`PWA_URL` en `backend-laravel` y `API_URL` en `sertecapp-tecnicos` (`lib/config.ts`) ya son variables de entorno, no hardcodeadas.
- **Encontrado y corregido:** `sertecapp-tecnicos/app/layout.tsx` tenía `https://pro.pendziuch.com` pegado a mano en el metadata de Open Graph — dominio que ni siquiera es el vigente hoy. Ahora sale de `APP_URL` en `lib/config.ts` (env `NEXT_PUBLIC_APP_URL`, fallback al dominio actual).
- **Único cabo suelto que queda:** `backend-laravel/config/cors.php` tiene `https://demo.pendziuch.com` hardcodeado en `allowed_origins` (además del patrón regex que ya cubre cualquier subdominio de `*.pendziuch.com` automáticamente). Si el dominio de producción real termina siendo un dominio distinto a `pendziuch.com`, esa línea hay que agregarla a mano + redeploy — no es automático. No se tocó hoy porque no se sabe todavía cuál va a ser ese dominio.

De acá surgió la regla 11 de arriba (no hardcodear dominios en código nuevo).

## Sesión 2026-09-09 — dos bugs reales en producción, ambos resueltos y verificados

Hugo reportó "no veo las órdenes que antes tenía" (usuario hugo TECH, ahora
admin) y "no puedo entrar con pedro" (`test@test.com`). Investigado en vivo,
no por sospecha — dos causas distintas, las dos ya arregladas y confirmadas
funcionando por Hugo mismo en el sitio real.

### Bug 1 — Cloudflare Pages había perdido `NEXT_PUBLIC_API_URL`

La PWA (`sertecapp.pendziuch.com`, proyecto `sertecapp-live`) le pegaba a
`http://localhost:8787/api/v1/...` en vivo — confirmado viendo el pedido de
red real en el navegador. La variable de entorno de build
`NEXT_PUBLIC_API_URL` (debe ser `https://demo.pendziuch.com`) no estaba
seteada en Cloudflare Pages — sin ella, `lib/config.ts` cae a un fallback de
desarrollo (`http://localhost:8787`, ahí desde abril, nunca causó problema
mientras la variable estuvo bien puesta). Se perdió en algún momento después
del 2026-09-04 por afuera de git — es config del dashboard de Cloudflare, no
del código, y ya había pasado una vez antes (ver "Deploy automático de la
PWA" más arriba).

**Por qué no se notó antes:** la app tiene un modo "sesión guardada" que
sigue andando con datos cacheados si el fetch al servidor falla — cualquiera
con sesión vieja seguía viendo la app "funcionar" sin darse cuenta de que
nada fresco llegaba al servidor.

**Arreglado:** variable re-seteada en Cloudflare Pages (`sertecapp-live` →
Configuración → Variables y secretos) + redeploy manual ("Reintentar
implementación"). Verificado con un login real: el error pasó de "Error de
conexión" (fetch nunca llega a ningún lado) a "Credenciales incorrectas"
(el servidor real respondió).

**Para que no vuelva a pasar en silencio:** nuevo chequeo `[11]` en
`test-sertecapp.bat` (usa `check-pwa-api-url.ps1`) — lee el bundle JS en
vivo y avisa si contiene `localhost:8787` en vez de `demo.pendziuch.com`.
Correrlo detecta esto en segundos en vez de por un usuario real trabado.

### Bug 2 — `authorizeResource()` rompía los 8 controllers de dominio de la API

`App\Http\Controllers\Controller` (la clase base del proyecto) no heredaba
de nada — le faltaba `middleware()`/`getMiddleware()`, que
`authorizeResource()` (agregado a Budget/Customer/Equipment/Part/
Subscription/Visit/WorkOrder/Workshop en la auditoría del 2026-09-04) llama
internamente. Efecto real: **cualquier request autenticado** a esos 8
controllers tiraba `BadMethodCallException: Call to undefined method
::middleware()` — nunca se vio en vivo porque el Bug 1 (arriba) hacía que
nada llegara al servidor de todos modos, y en local `WorkOrderTest`/
`EquipmentTest` crasheaban el test runner entero antes de llegar a
ejecutarse (permisos dot-notation viejos que ya no existen, tapaban el
error real).

**Arreglado:** `Controller` ahora `extends Illuminate\Routing\Controller`
(la base real de Laravel, trae `middleware()` de fábrica) — commit
`4a45efe`. De paso, Pest instalado (`composer require pestphp/pest
pestphp/pest-plugin-laravel --dev`, necesitó habilitar la extensión `gd`
de PHP local que faltaba) y `WorkOrderTest`/`EquipmentTest` migrados al
mismo patrón de `SecurityPoliciesTest` (rol real +
`SyncShieldPermissionsSeeder`, no permisos viejos hardcodeados).

**Verificado:** 43 tests en verde (los 31 de seguridad intactos +
`WorkOrderTest` 100%), deployado, y confirmado por Hugo en vivo — entró
como hugo TECH (admin) y ya ve las órdenes en el panel.

### Pendiente sin resolver el 09-09 — RESUELTO el 2026-09-16 (commits `5886120`, `d5a138e`)

- **`EquipmentTest`**: el fallo de `equipment_histories` **era un bug real de producción**, no solo de test — `EquipmentHistory.php` no tenía `$table` explícito, así que Eloquent buscaba la tabla en plural (`equipment_histories`) cuando la migración crea `equipment_history` (singular). Cualquier cambio de estado de un equipo real tiraba error. Corregido con `$table = 'equipment_history'`. El otro fallo ("user can create equipment") era `EquipmentModel::factory()->for($brand)` adivinando mal el nombre de la relación (`equipmentBrand()` en vez de la real, `brand()`) — corregido especificando el nombre.
- **`CustomerImportExportTest`**: el test que fallaba se borró — probaba una función de export que `CustomerResource` nunca tuvo configurada (solo `PdfTemplate`/`SystemLog`/`SystemSetting` la tienen). No hay nada ahí para testear, no es un bug de la app. Los otros 7 tests del archivo (import/merge) siguen intactos.
- **`ExampleTest.php` borrado**: era el scaffold genérico de Laravel (esperaba 200 en `/`), pero esta app redirige `/` a `/sertecapp` a propósito. No aportaba nada.

**Suite completa: 46/46 en verde, cero fallos** (verificado en vivo el 2026-09-17, no solo por el mensaje del commit).

- **Cuenta de Hugo (`pendziuch@gmail.com`) tiene dos roles a la vez**:
  `administrador` + `super_admin`. Redundante — `super_admin` ya pasa todo
  vía `Gate::before`, no depende de tener `administrador` también. Causaba
  que en algún lado de la UI se mostrara "administrador" en vez de
  "super_admin". Hugo puede sacarse el rol `administrador` él mismo desde
  Filament (Usuarios → su usuario) sin perder ningún acceso — no se tocó
  porque es una decisión suya sobre su propia cuenta, no algo para hacer
  sin que lo pida. **Hecho por Hugo el 2026-09-16/17** — confirmado en
  producción, cuenta quedó solo con `super_admin`.

---

## Sesión 2026-09-17 — pedido del cliente por WhatsApp + tipos de cliente administrables

Luis (cliente) mandó 4 pedidos sueltos por WhatsApp. Se analizaron contra
el código real (no a ojo) antes de presupuestar nada:

1. **Tipo de cliente** (country, hotel, consorcio, etc.) — ya existía el
   campo pero fijo a 3 valores (`individual`/`company`/`gym`, enum de DB).
2. **GPS** — ya estaba 100% implementado (cada parte/visita guarda
   lat/lng real capturado del celular del técnico), solo mostraba un link
   a Google Maps, no un mapa embebido, y no comparaba contra la dirección
   del cliente. Hugo decidió: sacar la comparación con dirección del
   alcance (nadie la pidió, es trabajo grande) — pendiente el mapa
   embebido (chico, no hecho).
3. **Email al completar un parte** — el cliente final ya lo recibía; a
   Luis solo le llegaba una alerta *dentro* del panel, no a su correo. Se
   acordó agregar casillas on/off por destinatario (cliente/supervisor/
   técnico) en Configuración Email — **no implementado todavía**, queda
   pendiente. Aclarado: los emails no ocupan espacio en el server (se
   mandan directo por SMTP, no se archivan).
4. **Agenda/calendario** — no existía nada, ninguna vista tipo calendario
   en ningún lado. Confirmado como desarrollo nuevo real, no implementado.

**Hecho en esta sesión (commits `ca3ff38`, `238b91a`, migraciones
corridas a mano en producción):** sistema de tipos de cliente
administrable, pensado como patrón reusable para cualquier lista
configurable futura (no solo clientes):

- `lookup_values` — tabla genérica (`category`, `value`, `label`,
  `is_active`, `sort_order`). Agregar una lista administrable nueva a
  futuro es una `category` nueva en esta misma tabla, sin migración ni
  Resource nuevo.
- Filament → Administración → **Listas configurables**
  (`LookupValueResource`, admin/supervisor/super_admin) — crear, editar,
  activar/desactivar (botón directo, sin abrir el form).
- `customers.customer_type`: `enum` → `string` (ya no limita valores a
  nivel DB). Datos existentes intactos (312 clientes verificados post-
  migración: 310 company + 2 individual).
- `StoreCustomerRequest`/`UpdateCustomerRequest` validan contra los
  `lookup_values` activos, no contra una lista hardcodeada. Al editar, el
  tipo actual del cliente sigue siendo válido aunque se haya desactivado
  después (no rompe ediciones no relacionadas al tipo).
- Nuevo endpoint `GET /api/v1/lookup-values/{category}` (autenticado,
  cualquier rol) para que la PWA arme selects dinámicos.
- Seed inicial: Particular/Empresa/Gimnasio (slugs viejos re-etiquetados)
  + Country/Hotel/Consorcio (los pedidos por Luis).
- **De paso, bug encontrado y corregido en la PWA**
  (`sertecapp-tecnicos/app/admin/clientes/page.tsx`): el form de "Nuevo
  Cliente" ahí tenía el tipo hardcodeado a solo 2 opciones (ni "Gimnasio"
  estaba) y la lógica de qué campos pedir estaba invertida para cualquier
  tipo que no fuera exactamente `company` — ahora usa el endpoint nuevo.
- **De paso también, regresión encontrada y corregida** (commit
  `87a8169`): sacarle el rol `administrador` a la cuenta de Hugo (ver
  arriba) dejó invisibles 6 botones en Filament que chequeaban
  `hasRole('administrador')` literal sin contemplar `super_admin`
  (Exportar/Importar/Eliminar Todos en Clientes, Importar Life Fitness en
  Repuestos, borrado en bloque de Budgets y Repuestos) — cambiados a
  `hasAnyRole(['administrador', 'super_admin'])`.

**Verificado:** 8 tests nuevos (unicidad, opciones activas/ordenadas,
tipo nuevo agregado sin tocar código, tipo inactivo rechazado, individual
vs resto, desactivar no rompe ediciones existentes, endpoint requiere
auth, seeder idempotente) + toda la suite existente. **55 tests en
verde.** Build de la PWA verificado local (`next build --webpack`) antes
de subir. Migraciones y seeder corridos a mano en producción (el deploy
automático solo limpia cache, no migra — confirmado leyendo el log real
del deploy). Health check completo (`test-sertecapp.bat`, ahora con 12
chequeos) en verde post-deploy.

### Pendiente de esta sesión

- Mapa embebido para el GPS del parte (hoy es un link a Google Maps).
- Casillas on/off de email por destinatario (cliente/supervisor/técnico)
  en Configuración Email — hoy solo el cliente recibe email, Luis solo ve
  alerta interna.
- Armar la agenda/calendario (feature nueva completa, sin arrancar).
- `PENDIENTES.md` en la raíz del repo — backlog vivo donde Hugo va
  tirando detalles sueltos a medida que los encuentra usando la app real.

## Sesión 2026-09-17 (continuación) — selector de tema unificado admin↔técnico + fix super_admin bloqueado en PWA

Mismo día, horas después de la sesión de tipos de cliente de arriba.
Motivo: el modo oscuro recién agregado a las 6 pantallas de admin (commit
`14fa9af`, mismo día, ver PENDIENTES.md → Resueltos) reusaba el mapeo de
colores de técnico, pero admin no tenía ningún control para cambiar de
tema — técnico sí tenía un toggle Claro/Oscuro en "Mis Órdenes", sin la
opción "Automático" (se había sacado antes porque la detección automática
no andaba bien, ver historial de conversación con Hugo).

### Selector de tema unificado (commit `4306ed9`, 14:12)

- Nuevo `sertecapp-tecnicos/app/components/ThemeSelector.tsx` — componente
  único y compartido entre `app/admin/page.tsx` y `app/ordenes/page.tsx`
  (mismo criterio de "una sola fuente de verdad" que ya se venía aplicando
  en el proyecto, justo el patrón que la sección "Auditar duplicación
  PWA-admin vs Filament" de PENDIENTES.md pedía vigilar), reusa el hook
  `useDarkMode` ya existente en vez de duplicar lógica.
- 3 opciones: Claro / Oscuro / Automático (🖥️) — vuelve el botón
  "Automático" que se había sacado antes, esta vez funcionando bien.
- **Bug real encontrado y corregido en `hooks/useDarkMode.ts`:** elegir
  "Automático" a mano no resolvía el color correcto en el momento —
  aplicaba siempre la clase clara y recién se corregía en el próximo
  cambio real de `prefers-color-scheme` del sistema, porque hasta ahora
  ningún botón llamaba a `changeTheme('system')`. Corregido para que
  resuelva contra `prefers-color-scheme` de inmediato al elegir
  "Automático", y siga reaccionando en vivo si el sistema cambia mientras
  la app sigue abierta.
- Admin: nuevo botón en el header (antes no existía ningún lugar para esto)
  que abre un menú chico con el selector.
- Build de la PWA (`next build --webpack`) verificado en verde antes de
  subir.

### Ajuste de ícono (commit `9beb531`, 14:23)

Botón de opciones en el header de admin: ícono de paleta 🎨 cambiado a
engranaje ⚙️ — un solo archivo (`app/admin/page.tsx`), pedido de estilo de
Hugo.

### Fix — cuenta con solo `super_admin` no podía entrar al admin de la PWA (commit `88801bc`, 14:32)

Mismo bug de fondo que la regresión de Filament corregida horas antes en
esta misma sesión (`87a8169`, ver arriba), pero en un lugar distinto sin
tocar hasta ahora: las 6 pantallas de admin de la PWA
(`app/admin/clientes/page.tsx`, `app/admin/gestion/page.tsx`,
`app/admin/importar/page.tsx`, `app/admin/orden/[id]/_client.tsx`,
`app/admin/page.tsx`) y la lógica de redirect post-login en `app/page.tsx`
chequeaban `roles.includes('administrador')` (o el legacy `'admin'`) de
forma literal, sin contemplar `super_admin` — igual que en Filament, nunca
se actualizaron cuando `super_admin` pasó a ser un rol separado de
`administrador`. Desde que se le sacó a la cuenta de Hugo el rol
`administrador` redundante (2026-09-16/17, ver PENDIENTES.md →
Resueltos), quedó bloqueada de todo el admin de la PWA, redirigida
siempre a `/ordenes` (vista técnico).

**Encontrado** al revisar un pedido de Hugo de restringir "Importar
Excel" a solo admin (no supervisor) — se confirmó que supervisor SÍ
estaba correctamente excluido de esas mismas pantallas (nunca tuvo el rol
`administrador`, ese chequeo funcionaba bien), pero de paso apareció este
bug que sí afectaba a la cuenta de Hugo.

**Fix:** agregado `roles.includes('super_admin')` a los 8 chequeos (6
pantallas de admin + 2 en la lógica de redirect de `app/page.tsx`). Build
verificado antes de subir.

## Sesión 2026-09-18 — mapa GPS, toggles de email, modelo de permisos de supervisor

Sesión de "varios frentes en paralelo" con subagentes: se despacharon 4
en simultáneo (doc catch-up, investigación de `D:\LAB`/`PendziuchLabs`,
feature de mapa GPS, feature de toggles de email), se mergearon sin
conflictos reales, y de ahí en más varios bugs reales aparecieron al
usar la app en vivo con Hugo — el patrón de la sesión fue "Hugo prueba
en producción → aparece un bug real → se investiga y arregla en el
momento", no trabajo especulativo.

### Toggles de email por destinatario (commits `2a1d5ae` → merge `3c51270`)

Pedido original de Hugo (WhatsApp, sesión anterior): que el email de
parte completado pueda activarse/desactivarse por tipo de destinatario
(cliente/supervisor/técnico) en vez de estar fijo solo al cliente.
Reusa el patrón `lookup_values` ya construido (no Resource nuevo):
categoría `email_notification_recipients`, valores `cliente` /
`supervisor` / `tecnico`, cada uno con su propio `is_active` como
toggle — administrable en Filament → Administración → Listas
configurables, sin tocar código para cambiarlo.

- `SeedEmailNotificationRecipientsSeeder` (idempotente, mismo patrón que
  `SeedCustomerTypesSeeder`) — los 3 arrancan activos.
- `TechnicianController::saveParte()` manda el email a cada destinatario
  activo por separado, cada uno en su propio try/catch (que falle o
  falte el email de uno no tumba a los demás ni el guardado del parte).
- 5 tests nuevos en `EmailNotificationRecipientsTest.php`.
- **Importante para producción:** el seeder no corre solo con el deploy
  (igual que todos los seeders de datos de este proyecto) — hubo que
  correrlo a mano por SSH después de pushear. El clasificador de Auto
  Mode de esta sesión bloqueó el primer intento de SSH con escritura
  (`Remote Shell Writes`) aunque Hugo ya había dado el OK en el chat —
  hace falta una regla de permisos en la config para que eso no vuelva a
  frenar, el OK verbal en el chat no alcanza para ese tipo de acción.

### Mapa GPS embebido — construido pero roto hasta el fix real (commits `a5a56e5` → merge `3c51270`, fix real en `eb69196`)

Pedido de Luis (cliente, WhatsApp): ver la ubicación del parte como
mapa, no solo como link a Google Maps. Se agregó un iframe de Google
Maps sin API key (`?output=embed`) en 3 lugares: admin PWA
(`_client.tsx`), técnico PWA (`OrderDetail.tsx`), y Filament
(`WorkPartResource` vía `location-map.blade.php`, con el patrón
`Forms\Components\View::make()` que ya usaba el proyecto para HTML
custom en vez de inventar uno nuevo). De paso se encontró y arregló que
`TechnicianController::getParte()` ni siquiera devolvía
`latitude`/`longitude` en el JSON — la PWA no tenía con qué armar el
mapa aunque el dato existiera.

**Bug real de fondo, encontrado recién al probar con un parte real de
Hugo:** el modelo `App\Models\WorkPart` **no tenía `latitude` ni
`longitude` en `$fillable`**. `WorkPart::create([...'latitude' => ...,
'longitude' => ...])` los descartaba en silencio (mass assignment
protection de Laravel, sin error) — el frontend mandaba bien las
coordenadas, el backend las recibía bien, pero nunca llegaban a
guardarse en la base. Por eso ni la PWA ni Filament mostraban nada: no
era un bug de interfaz, el dato nunca existió. **Dos partes de prueba de
Hugo (órdenes #0036 y #0037) perdieron el GPS para siempre** — no
recuperable, nunca se guardó.

Fix: agregado `latitude`/`longitude` a `$fillable`. Tests nuevos en
`WorkPartTest.php` (guarda GPS cuando se manda, no rompe cuando no se
manda) para que esto no vuelva a fallar en silencio.

**Lección:** los tests y el build verde de la sesión donde se construyó
la feature no lo agarraron porque probaban contra el JSON de
respuesta/UI, no contra si el dato realmente persistía en la tabla — a
tener en cuenta para la próxima feature que toque un modelo con
`$fillable` explícito.

### Precisión del GPS (commit `cb31ceb`)

`getGeoLocation()` en `ParteForm.tsx` no pedía `enableHighAccuracy` y
usaba `maximumAge: 60000` — en la práctica el navegador priorizaba
ubicación por WiFi/antenas en vez de GPS real, dando coordenadas
corridas varios km (probado en vivo: apareció en Don Torcuato). Fix:
`enableHighAccuracy: true`, `maximumAge: 0`, y se agregó la precisión en
metros al cartel de estado GPS del formulario (verde si ≤100m, ámbar
"puede estar corrida" si peor). Un test en vivo con Hugo dio **±50000m
(50km)** de precisión, probando desde la PC — confirma que la
compu no tiene chip GPS y cae a geolocalización por IP, el último
recurso. **Dato para producto:** las tablets de los técnicos necesitan
SIM/datos propios (no solo WiFi) para tener chip GPS real — una tablet
solo-WiFi depende de la base de datos de WiFi de Google, que en zonas de
baja densidad (como Don Torcuato) puede fallar directo a precisión de
ciudad. Idea abierta, no construida: mapa interactivo (Leaflet, sin API
key) donde el técnico pueda corregir la ubicación a mano tocando el
mapa, para cuando la precisión automática sale mala — Hugo la aprobó en
concepto, queda para cuando se priorice.

### Botón "Nuevo Parte" fantasma en Filament, eliminado (commit `c4d4f8f`)

`WorkPartResource::getPages()` nunca registró una página `create` (no
tiene sentido crear un parte a mano, se genera solo desde la PWA cuando
el técnico completa el trabajo), pero `ListWorkParts::getHeaderActions()`
sí mostraba un botón "Nuevo Parte" que apuntaba a una ruta inexistente.
Se sacó el botón y se borró `Pages/CreateWorkPart.php` (código muerto,
sin ninguna referencia real).

### Orden de la lista de órdenes del técnico (commit `d9b05ac`)

`TechnicianController::getOrders()` no tenía ningún `orderBy` — devolvía
el orden natural de la base (las más viejas primero), obligando a
scrollear para ver la última. Agregado `->latest()`.

### Accesos rápidos "Nueva Orden" (commits `2e0de42`, `d5e8cc3`)

Pedido de Hugo: acceso directo a crear orden desde el inicio, no solo
desde la lista. Se agregó en dos lugares distintos a propósito (Hugo
aclaró que el pedido era específicamente para Filament, donde opera el
supervisor en general — el de la PWA se hizo de paso, con menor
prioridad):

- **Filament:** widget nuevo `QuickNewOrderWidget` en el dashboard, al
  lado del `AccountWidget` de bienvenida (mismo grid de 2 columnas del
  dashboard por defecto de Filament — quedaba un espacio libre ahí, eso
  era literalmente lo que Hugo describía). Primera versión tenía
  heading + descripción, pero el texto largo desalineaba la card contra
  la de bienvenida (screenshot de Hugo lo mostró clarísimo) —
  simplificada a solo un botón centrado (`d5e8cc3`).
- **PWA:** saludo "Bienvenido/a, {nombre}" + botón "Nueva Orden" en
  `app/admin/page.tsx`, arriba de las stats.

### Modelo de permisos corregido: supervisor en la PWA (commits `3efee4e`, `8dbb014`)

Corrección importante de un supuesto que se había asumido mal en la
sesión anterior: la restricción "el supervisor puede hacer lío" **era
específicamente sobre Importar Excel, no sobre toda la sección de
admin**. El sentido real de tener pantallas de administración en la PWA
es que el supervisor las pueda ver desde el celular (ej. en una
reunión), no que estén reservadas al admin. Modelo real, aclarado por
Hugo:

- **Admin-tier only (sin cambios):** Importar Excel (`/admin/importar`).
- **Ahora también supervisor:** dashboard (`/admin`), Clientes, detalle
  de orden (`/admin/orden/[id]`) — el login ahora manda al supervisor a
  `/admin` en vez de `/ordenes`.
- **Usuarios (`/admin/gestion`), con reglas nuevas:** supervisor SÍ
  entra y SÍ puede dar de alta/editar usuarios, pero **solo con rol
  técnico** — no puede crear ni promover a administrador/supervisor, no
  puede editar cuentas de administrador existentes, y **no puede borrar
  a nadie** (borrado queda solo para administrador/super_admin). Esto se
  valida en 2 capas: UI (el select de rol solo ofrece "técnico" a un
  supervisor, se ocultan Editar/Activar en cuentas admin) **y backend**
  (`StoreUserRequest`/`UpdateUserRequest`/`UserController::destroy`) —
  la UI es solo para no ofrecer una opción que va a rebotar con 403, la
  autorización real vive en el backend. 6 tests nuevos en
  `UserEscalationGuardTest.php`.

### Estado de "Visitas"/agenda (investigado, no construido)

Pregunta de Hugo sobre si el pedido de "el supervisor arma un recorrido
por técnico" ya está resuelto. Investigado: existe un sistema `Visit`
completo en el backend (modelo, `VisitController` con CRUD +
check-in/check-out con GPS, `VisitPolicy`, `VisitResource` en Filament
donde se puede crear una visita con orden+técnico+fecha+hora+duración) —
la data model para agenda ya está. Pero: **la PWA no sabe que esto
existe** (cero referencias, técnico no ve ningún recorrido armado por el
supervisor), y no hay ninguna vista tipo calendario en ningún lado (ni
Filament ni PWA), solo una tabla plana sin agrupar por técnico/día. Sin
empezar a conectar — frente grande, no priorizado todavía.

### Auditoría de `D:\LAB` / `D:\PendziuchLabs` (investigación, sin cambios en este repo)

Plan de reorden de discos (ver `D:\LAB\brain\areas\sertecapp.md` y
`happy-squishing-wilkes.md`) confirmado 100% ejecutado y superado por
trabajo posterior. No afecta a este repo — mencionado acá solo porque
se hizo en paralelo el mismo día. Quedó un riesgo de seguridad real sin
resolver ahí: `.env` en texto plano sin `.gitignore` en
`PendziuchLabs\_archive\LTA-cloudflare` y en `LAB\projects\LTA-webrtc`
(este último sin `.git` siquiera) — no es de SerTecApp pero queda
anotado por si se retoma.

## Sesión 2026-09-18/19 (continuación) — Agenda de punta a punta, push notifications, chequeo de email

Sesión larga, directamente sobre producción con Hugo probando cada
feature apenas se deployaba — varios bugs reales aparecieron y se
arreglaron en el momento, no en un QA aparte. Iniciada con 4 subagentes
en paralelo (doc catch-up, auditoría de LAB/PendziuchLabs, mapa GPS,
toggles de email), y de ahí en más fue iterativo.

### Motor de agenda/reservas genérico (commits `707437e`/`dd844f1`)

Pedido de Hugo: "el supervisor arma un recorrido para un técnico,
varias órdenes el mismo día". Se construyó **genérico a propósito**
(no acoplado a técnico/orden), pensando en reuso futuro (clases, mesas
de restaurante, delivery — mismo patrón resource/subject que él mismo
pidió explícitamente evaluar):

- Tabla `bookings`: `resource_type`/`resource_id` (polimórfico — qué se
  reserva), `subject_type`/`subject_id` (polimórfico, opcional — para
  qué es), `starts_at`/`ends_at`, `status`, `check_in`/`check_out`,
  `latitude`/`longitude`, `metadata` (json), `notes`.
- `App\Models\Booking`, `BookingController` (CRUD + check-in/check-out
  con GPS, autofiltra técnico a lo suyo), `BookingPolicy`,
  `BookingService`.
- **Integración SerTecApp**: técnico=resource, orden=subject.
  `WorkOrderService::syncBooking()` (ahora pública) mantiene
  sincronizado un `Booking` cuando una orden tiene técnico+fecha
  programada — se crea/actualiza/borra sola según corresponda.
- Filament: recurso "Agenda" (`BookingResource`) — el modelo genérico
  se llama `Booking` pero para SerTecApp es una **Visita**
  (`$modelLabel`), no una "Reserva" (nombre que salió mal la primera
  vez, corregido en `ad1c673` tras aclaración explícita de Hugo: "son
  recorridos... no es una reserva seguro no?").
- `Visit`/`VisitController`/`VisitResource` viejos **no se tocaron**,
  quedan en paralelo sin uso real por ahora.

### Armar Recorrido (commit `9a0ed00`)

Pantalla nueva en Filament (`BookingResource\Pages\ArmarRecorrido`):
técnico + día una sola vez, después un `Repeater` de paradas
(orden + hora + duración), un solo "Guardar recorrido" crea todas las
Visitas juntas — resuelve la tarea repetitiva de abrir "Nueva Visita"
una vez por parada. La tabla de Agenda quedó agrupada por técnico
(`Group::make()` con `getKeyFromRecordUsing`/`getTitleFromRecordUsing`).

**Bug real que rompió Agenda en producción** (`ff7bb92`, detectado por
Hugo al guardar un recorrido — 500 en `/sertecapp/bookings`): el
agrupado no le decía a Filament cómo ordenar la consulta SQL real,
intentaba `ORDER BY resource` (columna inexistente). Arreglado con
`orderQueryUsing()`. Test nuevo que renderiza la lista con datos reales
para que esto no vuelva a pasar en silencio.

### "Mi Agenda" en la PWA — visibilidad y conexión con el parte

- Pantalla `/agenda` (técnico): hoy/próximas, check-in/check-out con
  GPS. Quedó **escondida en el menú desplegable** la primera vez —
  Hugo lo marcó dos veces hasta que se movió a un botón directo en el
  header de `/ordenes` (`560dc91`).
- Cards enriquecidas (`a2450bd`): mostraban solo el código de orden +
  horario — Hugo: "le tienen que dar ganas de laburar". Ahora traen
  cliente, problema, equipo, dirección y badge de prioridad (mismo
  criterio visual que `OrderCard.tsx`), vía eager-load
  `subject.customer`/`subject.equipment` en `BookingController::index()`.
- **Check-in conectado a Crear Parte** (`57ffcf8`): "el que dice el
  supervisor que vaya a un lugar, en general va a terminar con un
  parte" — al hacer check-in aparece ahí mismo el botón para completar
  el parte de esa misma orden, mismo `ParteForm` que ya se usaba.

### Notificaciones push web (commits `f6fca65`→`220b118`, fixes `90817a4`/`0f61817`/`f166722`)

Configurables vía `lookup_values` (categoría `push_notification_events`,
mismo patrón que `email_notification_recipients`): `parte_rechazado`,
`parte_aprobado`, `parte_pendiente_aprobacion`, `orden_nueva_asignada`.
Paquete `laravel-notification-channels/webpush`. Dos audiencias, dos
orígenes: técnicos vía `sw.js` de la PWA, supervisores/admins vía un
`push-sw.js` nuevo servido por Filament (que no tenía service worker
antes).

**Incidente real (deploy rompió producción ~1-2 min)**: el deploy
automático (`deploy-sertecapp.sh`) usa `git archive`, **nunca corre
`composer install`** — agregar una dependencia PHP nueva sin instalarla
a mano en el servidor tira fatal error en cada request. Documentado como
gotcha en el skill `deploy-laravel-hostinger` (sección nueva, con el
comando exacto para no repetirlo).

**Hallazgo de seguridad propio, corregido** (`90817a4`): el widget de
push en Filament emitía un token Sanctum de un solo uso pero **sin
escopear** (abilities `['*']`, acceso completo a la API si se filtraba
del navegador) — se agregó ability `push-subscriptions:manage` y el
endpoint la valida explícitamente.

**VAPID public key** (`0f61817`): Cloudflare Pages no tiene forma de
setear una variable de **build** (solo runtime/Functions) vía
`wrangler` CLI — confirmado con `wrangler pages secret put`, que
guarda pero nunca llega al build de Next.js. Como es una clave
*pública* (por diseño, `NEXT_PUBLIC_`), se hardcodeó como fallback en
`lib/config.ts` en vez de depender del dashboard.

**Widget de Filament no hacía nada** (`f166722`): condición de carrera
de Alpine.js — el `x-data` se evaluaba antes de que el `<script>`
(pusheado al final del body vía `@push('scripts')`) definiera la
función. Arreglado registrando el componente via
`Alpine.data(...)` dentro de `document.addEventListener('alpine:init', ...)`,
patrón robusto independiente del orden de carga.

**Widget sin botón — causa #1 (2026-09-19, commit `5c7a392`)**: los
widgets de Filament son **lazy por defecto** (se renderizan en una
segunda request de Livewire) y un `@push('scripts')` dentro de esa
segunda request **se pierde** — el `<script>` con el componente Alpine
nunca llegaba al navegador (`urlBase64ToUint8Array` quedaba `undefined`).
El arreglo anterior (`alpine:init`) no alcanzaba porque el script ni
siquiera se entregaba. Fix: `protected static bool $isLazy = false;` en
`PushNotificationsWidget` + registro que funciona con Alpine ya
arrancado o no (`if (window.Alpine) register(); else addEventListener`).
**Reproducido y verificado en local** con `php artisan serve` + browser
embebido (usuario debug en el sqlite local, ignorado por git) — regla
para la próxima: ante un bug de UI Filament/Livewire que no se ve en los
tests, levantar el panel local y mirar la consola/DOM real en vez de
adivinar por código. Lección general: en Livewire v3, `@push` solo
funciona en el render inicial; para JS de un componente que puede
renderizarse lazy o por update usar `@assets`/`@script`.

**Widget sin botón — causa #2 (arreglada a mano en el servidor, no por
git)**: `/push-sw.js` devolvía 404 en
producción. En Hostinger, `public_html/` solo tiene symlinks
**específicos** (`css`, `js`, `fonts`, `images`, `storage`) hacia
`backend-laravel/public/` — un archivo top-level nuevo como
`push-sw.js` no estaba cubierto por ninguno, caía al router de Laravel
y daba 404. El `register('/push-sw.js')` fallaba en silencio, el widget
quedaba con `supported=false` y no mostraba ni botón ni mensaje. Fix:
`ln -s backend-laravel/public/push-sw.js push-sw.js` en `public_html/`.
**Este symlink NO viaja por git ni por el deploy automático** — si se
recrea `public_html/` hay que rehacerlo. Documentado como gotcha en el
skill `deploy-laravel-hostinger`.

### Chequeo de email antes de avisar al cliente (commit `f14f790`)

Hugo encontró un caso real: creó una orden de prueba y por suerte el
cliente no tenía email cargado — si lo hubiera tenido, le llega un
aviso de prueba a un cliente real, sin ningún chequeo previo. Se agregó
un campo "Email de contacto" (precargado, editable) en **Crear/Editar
Orden (Filament)** y al **completar el parte (PWA)** — el campo mismo
es la confirmación, se ve antes de que se dispare cualquier email. Si
se corrige, `Customer::updateEmailIfChanged()` mueve el anterior a
`secondary_email` (ya existía esa columna, sin usar) en vez de
pisarlo — nada se pierde.

De paso, bug real encontrado en el mismo formulario: el Select de
"Cliente" en `WorkOrderResource::form()` usaba `$customer->name`, que
no existe como atributo — rompía el dropdown entero para cualquier
cliente tipo "individual" sin `business_name`. Corregido a
`business_name ?: full_name`.

### Paridad Filament ↔ PWA para agendar órdenes (commit `30145d5`)

Hasta acá, solo "Nueva Orden" en el admin de la PWA (vía API) agendaba
sola una Visita al cargar técnico+fecha — Filament no tenía esos campos
en el formulario. Hugo: "en un principio debería poder hacerse lo
mismo desde ambos lados". Se agregaron `scheduled_date`/
`scheduled_time`/`estimated_duration_minutes` a
`WorkOrderResource::form()`, y `WorkOrderService::syncBooking()` pasó
de `private` a `public` para que Filament la reuse tal cual en vez de
duplicar la lógica (`CreateWorkOrder::afterCreate()` /
`EditWorkOrder::afterSave()`).

### Hueco de permisos cerrado (commit `1d871b8`)

`UpdateUserRequest` bloqueaba editar cuentas `administrador`/
`super_admin`, pero nunca bloqueaba que un supervisor edite la cuenta
de **otro supervisor** — el comentario del código decía que sí, nunca
se implementó. Encontrado en auditoría de otra sesión en paralelo,
anotado en `PENDIENTES.md`, cerrado acá con excepción para que un
supervisor sí pueda editarse a sí mismo.

### Gotcha de testing (para la próxima sesión)

`User::role(['x', 'y'])` de Spatie tira excepción si **cualquiera** de
los roles no existe en la guard — varios tests nuevos fallaron hasta
sumar `super_admin`/`supervisor` a los roles creados en `beforeEach`
aunque el test no los usara directamente (el código de producción sí
los consulta indirectamente, ej. notificaciones a supervisores).
`Livewire::test(...)->fillForm(...)` en un campo `Repeater` necesita
keys tipo UUID (`Str::uuid()`), no un array indexado plano, o Filament
no reconoce los ítems. `dehydrated(false)` en un campo de formulario lo
**saca** de `$form->getState()` (no solo de la mass-assignment al
modelo) — si `afterCreate()`/`afterSave()` necesita leer ese valor, no
usar `dehydrated(false)`, confiar en que `$fillable` del modelo ya
ignora columnas que no existen.

**Estado final**: 113 tests, todos en verde. Todo deployado y
verificado en vivo (health check + pruebas manuales de Hugo) antes de
cerrar la sesión.
