# Guía: Integración Continua en Hostinger con GitHub Webhooks

> Última actualización: 2026-07-28 — Funcionando en producción ✅

## Qué logramos
Cada `deploy.bat "mensaje"` desde local actualiza Hostinger automáticamente en segundos sin intervención manual.

---

## Arquitectura

```
Local (Windows)
    ↓ deploy.bat "mensaje"
    ↓ robocopy backend-laravel/ → app/ (sincroniza paths)
    ↓ git commit + push
GitHub (repo)
    ↓ webhook POST a deploy.php
Hostinger deploy.php
    ↓ valida firma + ejecuta script
deploy-sertecapp.sh
    ↓ git pull + cp app/ → backend-laravel/app/ + artisan clear
Hostinger (producción actualizada ✅)
```

---

## El problema estructural del repo — LEER PRIMERO

Este repo tiene archivos en DOS paths distintos porque el repo raíz en Hostinger es `backend-laravel/` pero localmente es `SerTecApp/`.

| Path en repo GitHub | Quién lo usa |
|---|---|
| `app/Filament/...` | Hostinger (su repo root es `backend-laravel/`) |
| `backend-laravel/app/Filament/...` | Local (repo root es `SerTecApp/`) |

**La solución implementada:**
- `deploy.bat` usa robocopy para copiar `backend-laravel/` → `app/` antes de commitear → GitHub tiene ambos paths sincronizados
- `deploy-sertecapp.sh` en Hostinger hace `git pull` y luego copia `app/` → `backend-laravel/app/` → Hostinger queda actualizado

---

## Archivos clave

| Archivo | Ubicación | Función |
|---|---|---|
| `deploy.php` | `public_html/deploy.php` | Recibe webhook de GitHub, valida firma, lanza script |
| `deploy-sertecapp.sh` | `/home/u283281385/deploy-sertecapp.sh` | git pull + sincroniza paths + artisan clear |
| `deploy.bat` | `SerTecApp/deploy.bat` | Script local — sincroniza, commitea, pushea |

---

## Script de deploy en Hostinger (versión final)

`/home/u283281385/deploy-sertecapp.sh`:

```bash
#!/bin/bash
LARAVEL_DIR="/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel"
REPO_ROOT="/home/u283281385/domains/demo.pendziuch.com/public_html"
LOG="/tmp/sertecapp_deploy.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG"; }

log "=== DEPLOY INICIADO ==="

cd "$LARAVEL_DIR"

cp .env /tmp/sertecapp_env_backup

git pull origin development >> "$LOG" 2>&1

cp /tmp/sertecapp_env_backup .env

# Sincronizar app/ del repo raíz → backend-laravel/app/
# Resuelve el problema de los dos paths
if [ -d "$REPO_ROOT/app" ]; then
    cp -ru "$REPO_ROOT/app/." "$LARAVEL_DIR/app/"
    log "Archivos app/ sincronizados"
fi

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
```

Para crear/recrear este script en Hostinger:
```bash
cat > /home/u283281385/deploy-sertecapp.sh << 'EOF'
[contenido de arriba]
EOF
chmod +x /home/u283281385/deploy-sertecapp.sh
```

---

## Webhook en GitHub

URL: [github.com/PENDZIUCH/SerTecApp/settings/hooks](https://github.com/PENDZIUCH/SerTecApp/settings/hooks)

| Campo | Valor |
|---|---|
| Payload URL | `https://demo.pendziuch.com/deploy.php` |
| Content type | `application/json` |
| Secret | `SerTecDeploy2026!` |
| Events | Just the push event |
| Active | ✅ |

---

## Credenciales git en Hostinger

El repo necesita el token de GitHub en la URL del remote:

```bash
git remote set-url origin https://TOKEN@github.com/PENDZIUCH/SerTecApp.git
```

**Nunca pegar el token en el chat** — armarlo en bloc de notas y pegarlo directo en SSH.

Si el token expira: generar uno nuevo en [github.com/settings/tokens](https://github.com/settings/tokens) (scope: repo, sin expiración) y actualizar el remote.

---

## Uso diario

```cmd
deploy.bat "descripción del cambio"
```

El webhook dispara automáticamente. No hay paso manual adicional.

### Verificar deploy
```bash
tail -5 /tmp/sertecapp_deploy.log
```
Debe terminar con `=== DEPLOY COMPLETADO ===`.

---

## Solución de problemas

### git pull falla por conflicto local
```bash
git checkout -- app/Filament/Resources/ARCHIVO_EN_CONFLICTO.php
git pull origin development
```
Causa: archivo modificado manualmente en Hostinger. Siempre usar deploy.bat, nunca editar en Hostinger.

### El webhook no dispara
1. GitHub → Settings → Webhooks → ver el último delivery y el código de respuesta
2. Si da 403: el secret no coincide
3. Verificar log: `cat /tmp/sertecapp_deploy.log`

### git pull pide contraseña
El token expiró o se revocó. Generar uno nuevo y:
```bash
git remote set-url origin https://NUEVO_TOKEN@github.com/PENDZIUCH/SerTecApp.git
```

### Cambios no se ven después del deploy
```bash
cd /home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel
php artisan view:clear && php artisan cache:clear
```

---

## Reglas — no saltear

1. **NUNCA editar archivos directamente en Hostinger** — el próximo deploy va a generar conflicto
2. **NUNCA commitear desde Hostinger** — pisa los fixes de local
3. **NUNCA hacer git reset --hard en Hostinger** — rompe el repo
4. **SIEMPRE usar deploy.bat** — sincroniza los dos paths del repo
5. **El token de GitHub no va en el chat** — solo en la terminal

---

## Referencia rápida del servidor

| Dato | Valor |
|---|---|
| SSH | `ssh -i "C:\Users\Hugo Pendziuch\.ssh\hostinger_sertecapp" -p 65002 u283281385@147.79.103.125` |
| Laravel dir | `/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel` |
| Script deploy | `/home/u283281385/deploy-sertecapp.sh` |
| Log deploy | `/tmp/sertecapp_deploy.log` |
| Admin panel | `https://demo.pendziuch.com/sertecapp/login` |
