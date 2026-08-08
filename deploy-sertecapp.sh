#!/bin/bash
# deploy-sertecapp.sh
# Usa git archive para extraer contenido EXACTO del remote

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
git archive origin/development backend-laravel/lang/ | tar -xf - -C "$LARAVEL_DIR/" --strip-components=1 2>/dev/null || true
log "git archive extraido correctamente"

cp /tmp/sertecapp_env_backup "$LARAVEL_DIR/.env"
log ".env restaurado"

# Asegurar variables criticas en .env
sed -i 's/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' "$LARAVEL_DIR/.env"
sed -i 's/MAIL_MAILER=sendmail/MAIL_MAILER=smtp/' "$LARAVEL_DIR/.env"

# PWA_URL — agregar con salto de linea correcto si no existe
if ! grep -q 'PWA_URL' "$LARAVEL_DIR/.env"; then
    printf '\nPWA_URL=https://sertecapp.pendziuch.com\n' >> "$LARAVEL_DIR/.env"
fi

# Corregir si PWA_URL quedó pegado a otra linea (sin salto de linea previo)
sed -i 's/\([^[:space:]]\)PWA_URL/\1\nPWA_URL/g' "$LARAVEL_DIR/.env"

log "Variables criticas del .env verificadas"

/usr/bin/php artisan config:clear >> "$LOG" 2>&1
/usr/bin/php artisan cache:clear >> "$LOG" 2>&1
/usr/bin/php artisan view:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null

log "=== DEPLOY COMPLETADO ==="