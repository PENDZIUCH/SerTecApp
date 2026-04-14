# CHECKLIST: Deploy development a Hostinger

## 🔴 ANTES de deployar

- [ ] Estás en rama `development` — `git branch`
- [ ] Todos los cambios commitados — `git status` (limpio)
- [ ] Local funciona — `php artisan serve --port=8000`
- [ ] Probaste login en local — `/admin/login`
- [ ] Filament carga sin errores
- [ ] No hay errores en logs — `storage/logs/laravel.log`

## 🟡 PREPARACIÓN

**1. Limpiar worktree anterior (opcional pero recomendado)**
```bash
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"
git worktree remove backend-only-test --force
```

**2. Actualizar development con últimos cambios**
```bash
git checkout development
git pull origin development
git status  # Debe estar limpio
```

**3. Hacer commit final si hay cambios**
```bash
git add .
git commit -m "Deploy preparation: development branch"
git push origin development
```

## 🟢 DEPLOY A HOSTINGER

**Opción A: Usar script automatizado (RECOMENDADO)**

```bash
# Dar permisos de ejecución
chmod +x deploy-development-to-hostinger.sh

# Ejecutar
./deploy-development-to-hostinger.sh
```

**Opción B: Manual vía SSH**

```bash
ssh -p 65002 u283281385@147.79.103.125

cd /home/u283281385/domains/demos.pendziuch.com/public_html

# BACKUP por si algo falla
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

## 🔵 POST-DEPLOY VERIFICATION

```bash
# 1. Test en navegador
https://demos.pendziuch.com/admin/login

# 2. Login con demo credentials
Email: admin@sertecapp.local
Password: password

# 3. Verificar Filament carga
- Dashboard visible
- Menú lateral con recursos
- Usuarios, Clientes, Órdenes, etc.

# 4. Revisar logs en vivo
ssh -p 65002 u283281385@147.79.103.125
tail -f /home/u283281385/domains/demos.pendziuch.com/public_html/storage/logs/laravel.log
```

## 🔴 SI ALGO FALLA

**Error 403 en login POST:**
```bash
ssh -p 65002 u283281385@147.79.103.125
cd /home/u283281385/domains/demos.pendziuch.com/public_html

# Verificar .env
cat .env | grep -i session

# Limpiar cache completamente
php artisan cache:clear
php artisan config:clear
php artisan optimize:clear

# Reintentar
curl -I https://demos.pendziuch.com/admin/login
```

**Error 500 con vendor/autoload.php:**
```bash
# Composer install fallida
composer install --no-dev --optimize-autoloader --no-interaction

# O si hay problema de PHP/deps
composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php
```

**Error 404 en /admin:**
```bash
# Verificar .htaccess existe
ls -la /home/u283281385/domains/demos.pendziuch.com/public_html/.htaccess

# Si no existe, crear
cat > /home/u283281385/domains/demos.pendziuch.com/public_html/.htaccess << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
EOF
```

## ✅ CONFIRMACIÓN

Una vez deployado:

- [ ] `https://demos.pendziuch.com/admin/login` carga
- [ ] Login funciona con credenciales demo
- [ ] Dashboard Filament visible
- [ ] Todos los recursos accesibles (Usuarios, Clientes, etc.)
- [ ] API en `https://demos.pendziuch.com/api` responde
- [ ] No hay 500 errors en logs

## 📝 NOTAS IMPORTANTES

1. **NUNCA hagas `git reset` en producción** — destroza symlinks y config
2. **SIEMPRE backup antes de deploy** — `cp .env /tmp/env_backup_*`
3. **`composer install` es OBLIGATORIO** — sin vendor no funciona nada
4. **Migraciones deben ser idempotentes** — `--force` es seguro si están bien
5. **SESSION_DRIVER=database** — asegúrate que esté en `.env`
6. **DB_HOST=127.0.0.1** — NOT localhost (diferente en Hostinger)

## 🎯 SIGUIENTE PASO

Una vez que Hostinger restore el backup del demo anterior:
1. Deletea el contenido roto de `public_html`
2. Haz git clone nuevo de development
3. Ejecuta este checklist
4. Confirma que funciona

¡Listo! 🎉
