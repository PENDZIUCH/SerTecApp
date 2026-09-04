#!/bin/bash
set -e
cd /home/u283281385/domains/demos.pendziuch.com
rm -rf public_html app bootstrap config database resources routes storage tests artisan composer.* phpunit.xml .env vendor .git 2>/dev/null || true
git clone --branch development https://github.com/PENDZIUCH/SerTecApp.git temp 2>&1
cd temp/backend-laravel
cp -r * .env.example .* ../../ 2>/dev/null || true
cp -r public ../../public_html
cd ../../
rm -rf temp
composer install --no-dev --optimize-autoloader --no-interaction 2>&1
cat > .env << 'ENVEOF'
APP_NAME=SerTecApp
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
ENVEOF
php artisan key:generate --force 2>&1
php artisan migrate --force 2>&1
php artisan config:cache 2>&1
php artisan cache:clear 2>&1
php artisan optimize:clear 2>&1
chmod -R 775 storage bootstrap/cache 2>&1
echo 'DEPLOY COMPLETADO'
