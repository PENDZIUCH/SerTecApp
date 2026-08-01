# Guía: Integración Continua en Hostinger con GitHub Webhooks

> Última actualización: 2026-07-31 — Solución definitiva con git archive ✅

## Qué logramos
Cada `deploy.bat "mensaje"` desde local actualiza Hostinger automáticamente en segundos. El deploy usa `git archive` para extraer el contenido exacto del remote — sin comparar fechas, sin rsync, sin parches manuales.

---

## El problema que tuvimos (y cómo se resolvió)

El repo tiene archivos en DOS paths distintos porque el repo en Hostinger tiene su root en `backend-laravel/` pero localmente el root es `SerTecApp/`:

| Path en GitHub | Quién lo usa |
|---|---|
| `app/Filament/...` | Hostinger (su repo root es `backend-laravel/`) |
| `backend-laravel/app/Filament/...` | Local (repo root es `SerTecApp/`) |

**Intentos fallidos:**
- `cp -rf` — pierde contra archivos con fecha más reciente en Hostinger
- `rsync --checksum` — compara checksums pero el `public_html/app/` que usa como fuente está FUERA del repo de Hostinger y nunca se actualiza con `git pull`

**La solución correcta: `git archive`**

`git archive` lee directamente del objeto git en el remote — ignora fechas, ignora archivos en disco, siempre extrae el contenido exacto de lo que está en GitHub. No hay forma de que falle por problemas de fechas o archivos locales más nuevos.

```bash
git fetch origin development
git archive origin/development backend-laravel/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1
```

Lo que hace:
1. `git archive origin/development backend-laravel/` — crea un tar del contenido de `backend-laravel/` tal como está en GitHub
2. `--strip-components=1` — elimina el prefijo `backend-laravel/`
3. `-C "$LARAVEL_DIR/"` — extrae en `/public_html/backend-laravel/`
4. Resultado: archivos en el lugar correcto, con el contenido exacto de GitHub

---

## Arquitectura completa

```
Local (Windows)
    ↓ deploy.bat "mensaje"
    ↓ git add backend-laravel/ + commit + push
GitHub (repo, rama development)
    ↓ webhook POST a deploy.php
Hostinger deploy.php
    ↓ valida firma HMAC + lanza script en background
deploy-sertecapp.sh
    ↓ git fetch origin
    ↓ git archive origin/development backend-laravel/ | tar extract
    ↓ restaurar .env
    ↓ php artisan config:clear + cache:clear + view:clear
Hostinger (producción actualizada ✅ — siempre exactamente lo que está en GitHub)
```

---

## Script de deploy en Hostinger (versión final)

`/home/u283281385/deploy-sertecapp.sh`:

```bash
#!/bin/bash
LARAVEL_DIR="/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel"
LOG="/tmp/sertecapp_deploy.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG"; }

log "=== DEPLOY INICIADO ==="

cd "$LARAVEL_DIR"

cp .env /tmp/sertecapp_env_backup
log ".env respaldado"

git fetch origin development >> "$LOG" 2>&1
log "git fetch completado"

git archive origin/development backend-laravel/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1
log "git archive extraído correctamente"

cp /tmp/sertecapp_env_backup "$LARAVEL_DIR/.env"
log ".env restaurado"

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
```

Para crear/recrear en Hostinger:
```bash
cat > /home/u283281385/deploy-sertecapp.sh << 'EOF'
[contenido de arriba]
EOF
chmod +x /home/u283281385/deploy-sertecapp.sh
```

---

## deploy.bat local (versión simplificada)

```batch
@echo off
SET MSG=%~1
IF "%MSG%"=="" SET MSG=fix: actualizacion de codigo

cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"
git add backend-laravel/app/ backend-laravel/config/ backend-laravel/routes/ backend-laravel/resources/ CLAUDE.md deploy.bat deploy-sertecapp.sh sertecapp-tecnicos/
git diff --cached --quiet && echo "Sin cambios" || git commit -m "%MSG%"
git push origin development
```

**Sin robocopy** — ya no hace falta porque `git archive` en Hostinger toma directamente de `backend-laravel/` en el remote.

---

## Webhook en GitHub

[github.com/PENDZIUCH/SerTecApp/settings/hooks](https://github.com/PENDZIUCH/SerTecApp/settings/hooks)

| Campo | Valor |
|---|---|
| Payload URL | `https://demo.pendziuch.com/deploy.php` |
| Content type | `application/json` |
| Secret | `SerTecDeploy2026!` |
| Events | Just the push event |
| Branch | `development` |

---

## Credenciales git en Hostinger

```bash
git remote set-url origin https://TOKEN@github.com/PENDZIUCH/SerTecApp.git
```

Token: sin expiración, scope `repo`. Nunca pegarlo en el chat.

---

## Uso diario

```cmd
deploy.bat "descripción del cambio"
```

El webhook dispara automáticamente. Sin SSH manual. Sin parches.

### Verificar deploy
```bash
tail -5 /tmp/sertecapp_deploy.log
```
Debe terminar con `=== DEPLOY COMPLETADO ===`.

---

## Para futuros proyectos en Hostinger — hacerlo bien desde el principio

Para evitar el problema de doble path, clonar el repo directamente en el nivel correcto:

```bash
cd /home/u283281385/domains/nuevo-dominio.com/
git clone https://TOKEN@github.com/USER/REPO.git backend-laravel
```

Así `backend-laravel/` es el repo clonado completo. `git pull` dentro de él actualiza `app/`, `config/`, etc. directamente sin necesidad de `git archive`.

El `deploy-sertecapp.sh` en ese caso se simplifica a:
```bash
cd /home/.../backend-laravel
cp .env /tmp/env_backup
git pull origin main
cp /tmp/env_backup .env
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

---

## Solución de problemas

### El webhook no dispara
GitHub → Settings → Webhooks → ver el último delivery. Si da 403: el secret no coincide.

### git archive falla
```bash
git fetch origin development
git archive origin/development backend-laravel/ | tar -tf - | head -20
```
Si lista archivos, el archive funciona. Si da error, verificar que el remote esté accesible.

### git pull pide contraseña
El token expiró. Generar uno nuevo y:
```bash
git remote set-url origin https://NUEVO_TOKEN@github.com/PENDZIUCH/SerTecApp.git
```

### Rollback de emergencia
```bash
git checkout v0.1-stable  # vuelve al estado estable
```
O desde local hacer `git push --force origin v0.1-stable:development` y re-deployar.

---

## Referencia rápida

| Dato | Valor |
|---|---|
| SSH | `ssh -i "C:\Users\Hugo Pendziuch\.ssh\hostinger_sertecapp" -p 65002 u283281385@147.79.103.125` |
| Laravel dir | `/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel` |
| Script deploy | `/home/u283281385/deploy-sertecapp.sh` |
| Log deploy | `/tmp/sertecapp_deploy.log` |
| Admin panel | `https://demo.pendziuch.com/sertecapp/login` |
| Webhook secret | `SerTecDeploy2026!` |
| Tag estable | `v0.1-stable` |
