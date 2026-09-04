# SOLUCIÓN: Deployar `development` en Hostinger sin problemas

## Análisis: backend-only vs development

**Ambos tienen la misma arquitectura.** La única diferencia es que `development` agregó:
- ✅ Spatie Permission (roles y permisos dinámicos)
- ✅ UserResource en Filament (admin de usuarios)
- ✅ Migraciones nuevas (permisos, roles)
- ✅ Controllers API para usuarios

**backend-only era más simple pero eso NO lo hacía más estable.** Simplemente tenía menos features.

---

## Por qué development se rompe en Hostinger

**Root cause:** No es código. Es proceso de deploy incompleto.

Cuando hacemos `git pull` en Hostinger, falta:
1. ❌ `composer install --no-dev` — **CRÍTICO**
2. ❌ `php artisan migrate --force` — si hay nuevas migraciones
3. ❌ `php artisan config:cache` — caché stale
4. ❌ `php artisan cache:clear` — limpieza
5. ❌ `php artisan optimize:clear` — optimización

Resultado: Errores 403/500 porque `vendor/` no existe o está desactualizado.

---

## SOLUCIÓN: Deploy script correcto para Hostinger

Cuando subas `development` a producción (Hostinger), ejecuta **SIEMPRE** estos pasos:

```bash
#!/bin/bash
set -e

cd /home/u283281385/domains/demos.pendziuch.com/public_html/

echo "📥 Git pull..."
git pull origin development

echo "📦 Composer install..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "🔐 Configurar permisos..."
chmod -R 775 storage bootstrap/cache

echo "🧹 Limpiar cache..."
php artisan config:cache
php artisan cache:clear
php artisan optimize:clear

echo "📊 Ejecutar migraciones..."
php artisan migrate --force

echo "✅ Deploy completado"
```

**Crítico:** Sin `composer install`, `/vendor` está vacío o desactualizado.

---

## .env de Hostinger (correcto)

Asegúrate que el `.env` en Hostinger tenga:

```env
APP_NAME="SerTecApp"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demos.pendziuch.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u283281385_sertecappers
DB_USERNAME=u283281385_pendziuchala
DB_PASSWORD=poneteLasPilas2026

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

TRUSTED_PROXIES=*
FORCE_SCHEME=https
```

**Nota:** `127.0.0.1` en Hostinger es localhost de la VM. NOT `localhost`.

---

## .htaccess correcto

El archivo `/public_html/.htaccess` DEBE existir y tener:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

Esto redirecciona todo a `index.php` (público de Laravel).

---

## Estructura de directorios en Hostinger

Debe verse así:

```
/home/u283281385/domains/demos.pendziuch.com/
├── public_html/
│   ├── index.php              ← Laravel public/index.php
│   ├── .htaccess              ← Rewrite rules
│   ├── css/                   ← Symlink a backend-laravel/public/css
│   ├── js/                    ← Symlink a backend-laravel/public/js
│   └── ...
├── backend-laravel/           ← Clonado desde git
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── routes/
│   ├── storage/
│   ├── vendor/                ← Aquí va composer install
│   ├── artisan                ← DEBE estar en git
│   ├── composer.json
│   └── .env                   ← NO en git, creado en Hostinger
├── .env                       ← ROOT, mismo que Hostinger
└── ...
```

---

## Paso a paso: Deploy correcto

### 1. En local (antes de pushear)

```bash
# Asegúrate que todo funcione
php artisan serve --host=127.0.0.1 --port=8000

# Tests rápidos
php artisan tinker
>>> User::all()
>>> Role::all()

# Commit y push a GitHub
git add .
git commit -m "feat: nueva feature X"
git push origin development
```

### 2. En Hostinger (vía SSH)

```bash
ssh -p 65002 u283281385@147.79.103.125

cd /home/u283281385/domains/demos.pendziuch.com/public_html/

# BACKUP
cp .env /tmp/env_backup_$(date +%s).txt

# DEPLOY
git pull origin development
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan cache:clear
php artisan optimize:clear
chmod -R 775 storage bootstrap/cache

# VERIFICAR
curl -I https://demos.pendziuch.com/admin/login
```

---

## Errores comunes y soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| 500 `require vendor/autoload.php` | Falta `composer install` | `composer install --no-dev` |
| 403 en POST login | Middleware roto o SESSION_PATH | Revisar `.env` y middleware |
| 404 `/admin` | `.htaccess` falta o roto | Verificar rewrite rules |
| "No artisan" | `artisan` no está en git | `git add backend-laravel/artisan` |
| DB connection error | DB_HOST incorrecto | Debe ser `127.0.0.1` (no `localhost`) |

---

## Regla de ORO

**NUNCA hagas git reset en producción sin backup.**

Siempre:
```bash
cp -r /home/u283281385/domains/demos.pendziuch.com /tmp/backup_$(date +%s)
```

---

## development es production-ready

`development` tiene TODO lo que necesitás:
- ✅ Filament admin completo
- ✅ Roles y permisos dinámicos
- ✅ API REST para PWA
- ✅ Autenticación robusta
- ✅ Logs y auditoría
- ✅ Migraciones limpias

**El problema no es el código. Es el deploy.**

Usa el script arriba ↑ y va a funcionar.
