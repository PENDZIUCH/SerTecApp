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

# rsync --checksum: compara contenido del archivo, no fechas
# Siempre gana el repo, sin importar cuándo se editó en Hostinger
if [ -d "$REPO_ROOT/app" ]; then
    rsync -a --checksum --delete "$REPO_ROOT/app/" "$LARAVEL_DIR/app/"
    log "app/ sincronizado con rsync --checksum"
fi

if [ -d "$REPO_ROOT/config" ]; then
    rsync -a --checksum "$REPO_ROOT/config/" "$LARAVEL_DIR/config/"
    log "config/ sincronizado con rsync --checksum"
fi

if [ -d "$REPO_ROOT/routes" ]; then
    rsync -a --checksum "$REPO_ROOT/routes/" "$LARAVEL_DIR/routes/"
    log "routes/ sincronizado con rsync --checksum"
fi

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
