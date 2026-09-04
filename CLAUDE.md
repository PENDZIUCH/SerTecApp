# SerTecApp — Contexto para Claude

> Leer completo antes de hacer cualquier cosa.
> Última actualización: 2026-09-04
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
- **Rediseño completo Roles↔Permisos**: el panel "Roles" de Filament sigue sin controlar de verdad lo que cada rol puede hacer (las Policies ahora usan `hasAnyRole()` hardcodeado, no los permisos Spatie que se editan ahí — mismo motivo por el que Hugo vio "Administrador" sin nada tildado en Roles pero con acceso a Usuarios igual). Se hace junto con la convergencia a `core/v1` para no reescribir la lógica dos veces.
- **Convergencia con `core/v1`**: existe esa rama (última actividad 2026-08-08, antes de todo lo de hoy) con un sistema de módulos (`ModuleManager`) pensado para vender esto como Core a otros clientes — 85% del código ya es genérico según su propio `MODULES.md`. Decisión tomada con Hugo: converger a un solo código (traer la arquitectura de módulos de `core/v1` sobre `development`, no mantener dos ramas sincronizadas a mano) — pendiente de ejecutar.
- **Magic link de larga vida (30-365 días) viajando en texto plano por WhatsApp/email** — cambiarlo a un token de un solo uso de corta vida cambia el flujo de UX, se planifica aparte con Hugo antes de tocarlo.
- PDF de presupuestos (`routes/web.php`) da 500 en vez de un redirect prolijo para un usuario sin sesión — bug preexistente de manejo de errores, no vinculado a esta auditoría, no es un hueco de seguridad (igual bloquea el acceso), queda anotado por si molesta.
