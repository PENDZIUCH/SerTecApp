# Guía: Integración Continua en Hostinger con GitHub Webhooks

> Última actualización: 2026-07-28 — Funcionando en producción ✅

## Qué logramos
Cada `deploy.bat "mensaje"` desde local actualiza Hostinger automáticamente en segundos sin intervención manual.

---

## El problema estructural — leer antes de empezar

Este proyecto tiene un problema de estructura heredado: el repo en Hostinger tiene su root en `backend-laravel/` pero localmente el root es `SerTecApp/`. Esto genera dos paths distintos para los mismos archivos:

| Path en GitHub | Quién lo usa |
|---|---|
| `app/Filament/...` | Hostinger (su repo root es `backend-laravel/`) |
| `backend-laravel/app/Filament/...` | Local (repo root es `SerTecApp/`) |

**Para futuros proyectos: evitar esto clonando el repo en Hostinger desde el nivel correcto.** Ver sección "Cómo hacer bien un deploy nuevo desde cero" al final.

---

## Solución implementada

**`deploy.bat`** (local) sincroniza los subdirectorios de `backend-laravel/` a la raíz del repo antes de commitear:

```
backend-laravel/app/     → app/
backend-laravel/config/  → config/
backend-laravel/routes/  → routes/
backend-laravel/resources/ → resources/
```

**`deploy-sertecapp.sh`** (Hostinger) hace `git pull` y luego copia los archivos del repo raíz al directorio de Laravel:

```
app/     → backend-laravel/app/
config/  → backend-laravel/config/
```

---

## Arquitectura completa

```
Local (Windows)
    ↓ deploy.bat "mensaje"
    ↓ robocopy: backend-laravel/{app,config,routes,resources} → raíz del repo
    ↓ git commit + push
GitHub (repo)
    ↓ webhook POST a deploy.php
Hostinger deploy.php
    ↓ valida firma HMAC + lanza script en background
deploy-sertecapp.sh
    ↓ git pull
    ↓ cp app/ → backend-laravel/app/
    ↓ cp config/ → backend-laravel/config/
    ↓ php artisan config:clear + cache:clear + view:clear
Hostinger (producción actualizada ✅)
```

---

## Archivos clave

| Archivo | Ubicación | Función |
|---|---|---|
| `deploy.bat` | `SerTecApp/deploy.bat` | Script local — sincroniza, commitea, pushea |
| `deploy.php` | `public_html/deploy.php` | Recibe webhook de GitHub, valida firma, lanza script |
| `deploy-sertecapp.sh` | `/home/u283281385/deploy-sertecapp.sh` | git pull + sincroniza paths + artisan clear |

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

# Sincronizar app/ y config/ del repo raíz → backend-laravel/
if [ -d "$REPO_ROOT/app" ]; then
    cp -ru "$REPO_ROOT/app/." "$LARAVEL_DIR/app/"
    log "app/ sincronizado"
fi

if [ -d "$REPO_ROOT/config" ]; then
    cp -ru "$REPO_ROOT/config/." "$LARAVEL_DIR/config/"
    log "config/ sincronizado"
fi

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
```

Para crear o recrear este script en Hostinger (una sola vez):
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

El repo necesita el token de GitHub en la URL del remote para hacer pull sin pedir contraseña:

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

Eso hace todo. El webhook dispara automáticamente. No hay paso manual adicional.

### Verificar deploy
```bash
tail -5 /tmp/sertecapp_deploy.log
```
Debe terminar con `=== DEPLOY COMPLETADO ===`.

---

## Cómo hacer bien un deploy nuevo desde cero (futuros proyectos)

Para evitar el problema de los dos paths, seguir este orden desde el principio:

**1. Estructura local correcta**

El repo debe tener el proyecto Laravel en la raíz, no en un subdirectorio:
```
mi-proyecto/          ← repo git root
├── app/
├── config/
├── routes/
├── .env.example
└── ...
```

**2. Clonar en Hostinger desde el nivel correcto**

```bash
cd /home/u283281385/domains/mi-dominio.com/
git clone https://TOKEN@github.com/USER/REPO.git backend-laravel
```

Así `backend-laravel/` es el repo clonado completo, y `git pull` dentro de él actualiza directamente `app/`, `config/`, etc. sin necesidad de sincronización extra.

**3. Configurar el public_html**

```bash
# index.php en public_html que apunta al proyecto
echo '<?php require __DIR__ . "/../backend-laravel/public/index.php";' > /home/u283281385/domains/mi-dominio.com/public_html/index.php
```

**4. Script de deploy simplificado (sin doble path)**

Para un proyecto bien estructurado, el script de deploy es mucho más simple:
```bash
#!/bin/bash
cd /home/u283281385/domains/mi-dominio.com/backend-laravel
cp .env /tmp/env_backup
git pull origin main
cp /tmp/env_backup .env
php artisan config:clear && php artisan cache:clear && php artisan view:clear
```

**5. deploy.bat local simplificado**

```batch
cd "C:\ruta\al\proyecto"
git add .
git commit -m "%~1"
git push origin main
```

Sin robocopy, sin sincronización de paths — directo.

---

## Solución de problemas

### git pull falla por conflicto
```bash
git checkout -- archivo/en/conflicto.php
git pull origin development
```
**Causa:** archivo editado directamente en Hostinger. Nunca editar en producción.

### Cambio en config/ no se refleja en producción
Verificar que `deploy-sertecapp.sh` tenga el bloque de sincronización de `config/`. Si no lo tiene, recrear el script desde la sección anterior.

### El webhook no dispara
GitHub → Settings → Webhooks → ver el último delivery. Si da 403: el secret no coincide. Verificar log: `cat /tmp/sertecapp_deploy.log`

### git pull pide contraseña
El token expiró. Generar uno nuevo y actualizar el remote:
```bash
git remote set-url origin https://NUEVO_TOKEN@github.com/USER/REPO.git
```

### Los cambios no se ven después del deploy
```bash
cd /home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel
php artisan optimize:clear
```

---

## Reglas — no saltear

1. **NUNCA editar archivos directamente en Hostinger** — el próximo deploy genera conflicto
2. **NUNCA commitear desde Hostinger** — pisa los fixes de local
3. **NUNCA hacer git reset --hard en Hostinger** sin entender el estado
4. **SIEMPRE usar deploy.bat** — sincroniza todos los paths del repo
5. **El token de GitHub no va en el chat** — solo en la terminal

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
