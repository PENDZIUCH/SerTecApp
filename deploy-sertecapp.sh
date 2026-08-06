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
git archive origin/development backend-laravel/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1
log "git archive extraído correctamente"

# Restaurar .env
cp /tmp/sertecapp_env_backup "$LARAVEL_DIR/.env"
log ".env restaurado"

# Asegurar variables críticas en .env
# QUEUE_CONNECTION debe ser sync en Hostinger — no hay workers permanentes
sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' "$LARAVEL_DIR/.env"
# MAIL_MAILER debe ser smtp — sendmail está deshabilitado en Hostinger
sed -i 's/MAIL_MAILER=sendmail/MAIL_MAILER=smtp/' "$LARAVEL_DIR/.env"
log "Variables críticas del .env verificadas"

# Artisan
/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="
