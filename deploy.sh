#!/bin/bash
# Auto-deploy script para Hostinger
# Se ejecuta con: git push y post-receive hook

set -e

APP_DIR="/home/u283281385/domains/demos.pendziuch.com/public_html"
BRANCH="development"
LOG_FILE="$APP_DIR/deploy.log"

echo "[$(date)] Deploy iniciado" >> $LOG_FILE

# 1. Pull latest code
cd $APP_DIR
git fetch origin
git reset --hard origin/$BRANCH

# 2. Copy SQLite from local (via scp - ejecutar desde local) o usar db migrada
# NOTA: Para automatizar completamente, necesitamos que SQLite se sincronice vía git LFS o similar

# 3. Laravel tasks
cd $APP_DIR/backend-laravel
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:clear
php artisan cache:clear

# 4. Verificar datos
COUNT=$(php artisan tinker --execute "echo \DB::table('customers')->count();" 2>/dev/null | tail -1)
echo "[$(date)] Clientes en BD: $COUNT" >> $LOG_FILE

echo "[$(date)] Deploy completado" >> $LOG_FILE
