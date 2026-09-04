#!/bin/bash
set -e

cd /home/u283281385/domains/demos.pendziuch.com

echo "🧹 Limpiando..."
rm -rf public_html temp 2>/dev/null || true

echo "📥 Clone development..."
git clone --branch development --depth 1 https://github.com/PENDZIUCH/SerTecApp.git temp

echo "📂 Moviendo Laravel..."
cd temp/backend-laravel
for item in app bootstrap config database resources routes storage tests artisan composer.json composer.lock phpunit.xml .env.example; do
  [ -e "$item" ] && mv "$item" ../../
done
mv public ../../public_html
cd ../..
rm -rf temp

echo "📦 Composer..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🔧 .env..."
cat > .env << 'ENVFILE'
APP_NAME="SerTecApp"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demos.pendziuch.com
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u283281385_sertecappers
DB_USERNAME=u283281385_pendziuchala
DB_PASSWORD=poneteLasPilas2026
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
MAIL_MAILER=log
TRUSTED_PROXIES=*
ENVFILE

echo "🔑 Key..."
php artisan key:generate --force

echo "📊 Migrations..."
php artisan migrate --force

echo "🧹 Cache..."
php artisan config:cache && php artisan cache:clear && php artisan optimize:clear

echo "🔐 Permisos..."
chmod -R 775 storage bootstrap/cache

echo "✅ DEPLOY COMPLETADO"
echo ""
echo "Verificando..."
curl -I https://demos.pendziuch.com/admin/login
