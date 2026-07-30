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
# Sin -u para SIEMPRE sobreescribir independientemente de fechas
if [ -d "$REPO_ROOT/app" ]; then
    cp -rf "$REPO_ROOT/app/." "$LARAVEL_DIR/app/"
    log "app/ sincronizado (forzado)"
fi

if [ -d "$REPO_ROOT/config" ]; then
    cp -rf "$REPO_ROOT/config/." "$LARAVEL_DIR/config/"
    log "config/ sincronizado (forzado)"
fi

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
