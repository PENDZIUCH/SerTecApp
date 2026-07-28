# Guía: Integración Continua en Hostinger con GitHub Webhooks

## Qué logramos
Cada `git push origin development` desde local actualiza Hostinger automáticamente en segundos.

---

## Arquitectura

```
Local (Windows)
    ↓ git push
GitHub (repo)
    ↓ webhook POST
Hostinger deploy.php
    ↓ ejecuta
deploy-sertecapp.sh
    ↓ git pull + artisan clear
Hostinger (producción actualizada)
```

---

## Estructura del repo — IMPORTANTE

Este repo tiene una particularidad: los archivos existen en DOS paths distintos.

| Path en repo | Quién lo usa |
|---|---|
| `app/Filament/...` | Hostinger (repo root = `backend-laravel/`) |
| `backend-laravel/app/Filament/...` | Local (repo root = `SerTecApp/`) |

**Por eso antes de cada commit hay que sincronizar con robocopy** (ver deploy.bat).

---

## Archivos clave

| Archivo | Ubicación | Función |
|---|---|---|
| `deploy.php` | `public_html/deploy.php` | Recibe el webhook de GitHub, valida la firma y lanza el script |
| `deploy-sertecapp.sh` | `/home/u283281385/deploy-sertecapp.sh` | Hace git pull + artisan clear en Hostinger |
| `deploy.bat` | `SerTecApp/deploy.bat` | Script local que sincroniza, commitea, pushea |

---

## Cómo se configuró — paso a paso

### 1. Script de deploy en Hostinger
Crear `/home/u283281385/deploy-sertecapp.sh` con este contenido:

```bash
#!/bin/bash
LARAVEL_DIR="/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel"
LOG="/tmp/sertecapp_deploy.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG"; }

log "=== DEPLOY INICIADO ==="

cd "$LARAVEL_DIR"

cp .env /tmp/sertecapp_env_backup
git pull origin development >> "$LOG" 2>&1
cp /tmp/sertecapp_env_backup .env

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
```

```bash
chmod +x /home/u283281385/deploy-sertecapp.sh
```

### 2. Archivo deploy.php
Ya existe en `public_html/deploy.php`. Configuración:
- **Secret:** `SerTecDeploy2026!`
- **Branch:** `development`
- Valida la firma de GitHub antes de ejecutar nada

### 3. Webhook en GitHub
URL: `https://github.com/PENDZIUCH/SerTecApp/settings/hooks`

Configuración:
- **Payload URL:** `https://demo.pendziuch.com/deploy.php`
- **Content type:** `application/json`
- **Secret:** `SerTecDeploy2026!`
- **Events:** Just the push event
- **Active:** ✅

### 4. Credenciales git en Hostinger
El repo necesita el token de GitHub en la URL del remote para hacer pull sin pedir contraseña:

```bash
git remote set-url origin https://TOKEN@github.com/PENDZIUCH/SerTecApp.git
```

Nunca pegar el token en el chat — armarlo en un bloc de notas y pegarlo directamente en SSH.

---

## Uso diario

### Deploy con un comando
```cmd
deploy.bat "descripción del cambio"
```

Lo que hace internamente:
1. `robocopy backend-laravel . /E /XO` — sincroniza archivos al path de Hostinger
2. `git add + commit + push` — sube a GitHub
3. GitHub dispara el webhook automáticamente
4. Hostinger hace `git pull + artisan clear`

### Verificar que el deploy funcionó
En SSH:
```bash
tail -10 /tmp/sertecapp_deploy.log
```

Debe terminar con:
```
[fecha] === DEPLOY COMPLETADO ===
```

---

## Solución de problemas

### El webhook no dispara
1. Verificar en GitHub → Settings → Webhooks → ver el último delivery
2. Si da 403: el secret no coincide
3. Si da 500: error en deploy.php — revisar con `cat /tmp/sertecapp_deploy.log`

### git pull falla en Hostinger
```bash
cd /home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel
git remote -v  # verificar que tiene el token en la URL
git pull origin development
```

Si pide contraseña: el token expiró o se revocó — generar uno nuevo en GitHub y actualizar el remote.

### Los cambios no se ven en producción después del deploy
```bash
cd /home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel
php artisan view:clear && php artisan cache:clear
```

### El deploy.bat no commitea cambios
Verificar que los archivos modificados estén bajo `backend-laravel/app/`, `backend-laravel/config/` o `backend-laravel/routes/`. Si están en otra carpeta, agregarlos manualmente con `git add`.

---

## Reglas importantes

1. **NUNCA commitear desde Hostinger** — pisa los fixes de local
2. **NUNCA hacer `git reset --hard` en Hostinger** — puede romper el repo
3. **SIEMPRE usar deploy.bat** para pushear — sincroniza los dos paths del repo
4. **El .env de Hostinger nunca se toca con git** — el script lo resguarda antes del pull
5. **El token de GitHub no va en el chat** — solo en la terminal directamente

---

## Credenciales y datos del servidor

| Dato | Valor |
|---|---|
| SSH | `ssh -i "C:\Users\Hugo Pendziuch\.ssh\hostinger_sertecapp" -p 65002 u283281385@147.79.103.125` |
| Deploy URL | `https://demo.pendziuch.com/deploy.php` |
| Deploy Secret | `SerTecDeploy2026!` |
| Laravel dir | `/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel` |
| Log deploy | `/tmp/sertecapp_deploy.log` |
| Script deploy | `/home/u283281385/deploy-sertecapp.sh` |
