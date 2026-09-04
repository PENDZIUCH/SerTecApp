#!/bin/bash
set -e
cd /home/u283281385/domains/demos.pendziuch.com
echo "Limpiando..."
rm -rf public_html temp 2>/dev/null || true
echo "Clone..."
git clone --branch development --depth 1 https://github.com/PENDZIUCH/SerTecApp.git temp
cd temp/backend-laravel
echo "Moviendo..."
mv app bootstrap config database resources routes storage tests artisan composer.json composer.lock phpunit.xml .env.example ../../ 2>/dev/null || true
mv public ../../public_html
cd ../..
rm -rf temp
echo "Composer..."
composer install --no-dev --optimize-autoloader --no-interaction
echo "Creando .env..."
cat > .env << 'EOF'
APP_NAME="SerTecApp"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demos.pendziuch.com
APP_LOCALE=es
LOG_CHANNEL=stack
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u283281385_sertecappers
DB_USERNAME=u283281385_pendziuchala
DB_PASSWORD=poneteLasPilas2026
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
TRUSTED_PROXIES=*
EOF
echo "Key..."
php artisan key:generate --force
echo "Migraciones..."
php artisan migrate --force
echo "Cache..."
php artisan config:cache
php artisan cache:clear
php artisan optimize:clear
echo "Permisos..."
chmod -R 775 storage bootstrap/cache
echo "✅ DEPLOY COMPLETO"
