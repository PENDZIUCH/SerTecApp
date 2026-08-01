#!/bin/bash
# deploy-sertecapp.sh
# Usa git archive para extraer contenido EXACTO del remote
# Elimina el problema de fechas/rsync definitivamente

LARAVEL_DIR="/home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel"
LOG="/tmp/sertecapp_deploy.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG"; }

log "=== DEPLOY INICIADO ==="

cd "$LARAVEL_DIR"

# Backup del .env antes de cualquier cambio
cp .env /tmp/sertecapp_env_backup
log ".env respaldado"

# Traer últimos cambios del remote
git fetch origin development >> "$LOG" 2>&1
log "git fetch completado"

# Extraer el contenido exacto de backend-laravel/ del remote
# git archive lee del objeto git directamente — ignora fechas y archivos locales
# --strip-components=1 elimina el prefijo backend-laravel/
# El resultado se extrae en LARAVEL_DIR con los paths correctos
git archive origin/development backend-laravel/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1
log "git archive extraído correctamente"

# Restaurar .env (git archive no lo toca porque está en .gitignore)
cp /tmp/sertecapp_env_backup "$LARAVEL_DIR/.env"
log ".env restaurado"

# Artisan
/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
