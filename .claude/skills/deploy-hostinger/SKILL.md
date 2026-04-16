---
name: deploy-hostinger
description: Deploy SerTecApp (rama development) a demo.pendziuch.com en Hostinger. SSH, composer, migrate, verify. Todo automatizado.
argument-hint: "[first-time|update|verify]"
---

# Deploy SerTecApp → demo.pendziuch.com

Argumento: `$0` = modo (`first-time` | `update` | `verify`)
Si no se pasa argumento, asumir `update`.

**Datos del entorno (ya configurados, no preguntar):**
- Dominio: `demo.pendziuch.com`
- SSH: `ssh -i ~/.ssh/hostinger_sertecapp -p 65002 u283281385@147.79.103.125`
- Repo: `https://github.com/PENDZIUCH/SerTecApp.git` rama `development`
- Panel Filament: `/sertecapp`
- Script deploy: `~/deploy-sertecapp.sh`

---

## Modo: `verify` — solo verificar que todo funciona

```bash
DOMAIN="demo.pendziuch.com"

echo "=== Verificación SerTecApp ==="

echo -n "Panel login:   " && curl -s -o /dev/null -w "%{http_code}\n" "https://$DOMAIN/sertecapp/login"
echo -n "Raíz redirect: " && curl -s -o /dev/null -w "%{http_code} → %{redirect_url}\n" "https://$DOMAIN/"
echo -n "API health:    " && curl -s "https://$DOMAIN/api/health" | grep -o '"status":"[^"]*"'
echo -n "API sin token: " && curl -s -o /dev/null -w "%{http_code} (esperado 401)\n" "https://$DOMAIN/api/v1/customers" -H "Accept: application/json"

TOKEN=$(curl -s -X POST "https://$DOMAIN/api/v1/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"pendziuch@gmail.com","password":"SerTecApp2026!"}' \
  | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

if [ -n "$TOKEN" ]; then
  echo "API login:     OK (token obtenido)"
  for ep in customers work-orders parts users; do
    STATUS=$(curl -s -o /tmp/r.txt -w "%{http_code}" "https://$DOMAIN/api/v1/$ep" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json")
    TOTAL=$(grep -o '"total":[0-9]*' /tmp/r.txt | head -1)
    printf "  [%s] /api/v1/%-20s %s\n" "$STATUS" "$ep" "$TOTAL"
  done
else
  echo "API login:     FALLO — revisar credenciales o estado del servidor"
fi
```

Si todo da verde, reportar al usuario y listo. Si algo falla, ir al diagnóstico al final.

---

## Modo: `update` — actualizar código en el servidor

### Paso 1: Pre-flight local (antes de pushear)

Verificar que el código local tiene los requisitos para Hostinger:

```bash
# 1. FilamentUser implementado
grep -n "FilamentUser\|canAccessPanel" backend-laravel/app/Models/User.php
```
Si no aparece → hay que agregarlo antes de deployar.

```bash
# 2. Path de Filament no es /admin
grep -n "->path\|->id" backend-laravel/app/Providers/Filament/AdminPanelProvider.php
```
Si dice `path('admin')` → hay que cambiarlo.

```bash
# 3. No hay applyFilters() huérfanos
grep -rn "applyFilters" backend-laravel/app/Http/Controllers/
```
Si aparece → reemplazar con filtros manuales antes de deployar.

```bash
# 4. Push al repo
git push origin development
```

### Paso 2: Ejecutar deploy en servidor

```bash
ssh -n -i "C:/Users/Hugo Pendziuch/.ssh/hostinger_sertecapp" -o StrictHostKeyChecking=no -p 65002 u283281385@147.79.103.125 "~/deploy-sertecapp.sh" 2>/dev/null
```

Ver resultado:
```bash
ssh -n -i "C:/Users/Hugo Pendziuch/.ssh/hostinger_sertecapp" -o StrictHostKeyChecking=no -p 65002 u283281385@147.79.103.125 "tail -20 /tmp/sertecapp_deploy.log" 2>/dev/null
```

### Paso 3: Verificar

Correr el modo `verify` (arriba).

---

## Modo: `first-time` — primera instalación completa

### Paso 1: Pre-flight (igual que update, Paso 1)

### Paso 2: Crear estructura en el servidor

```bash
ssh -i "C:/Users/Hugo Pendziuch/.ssh/hostinger_sertecapp" -p 65002 u283281385@147.79.103.125
```

Una vez dentro del servidor, correr:

```bash
PUBLIC="/home/u283281385/domains/demo.pendziuch.com/public_html"
LARAVEL="$PUBLIC/backend-laravel"

# Clonar repo
cd /tmp
git clone --branch development --depth 1 https://github.com/PENDZIUCH/SerTecApp.git sertec_tmp
cp -r sertec_tmp/backend-laravel/. "$LARAVEL/"
rm -rf sertec_tmp

# .htaccess CRÍTICO — PHP 8.3
cat > "$PUBLIC/.htaccess" << 'EOF'
AddHandler application/x-httpd-php83 .php
SecRuleRemoveById 400011
SecRuleRemoveById 400012
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [QSA,L]
</IfModule>
EOF

# index.php bridge
cat > "$PUBLIC/index.php" << 'EOF'
<?php
foreach (['REMOTE_ADDR','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED_PROTO','HTTP_X_FORWARDED_HOST'] as $k) {
    if (isset($_SERVER[$k]) && is_array($_SERVER[$k])) $_SERVER[$k] = reset($_SERVER[$k]);
}
if (empty($_SERVER['REMOTE_ADDR'])) $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require __DIR__ . '/backend-laravel/public/index.php';
EOF

# Symlinks
cd "$PUBLIC"
ln -sfn backend-laravel/public/css css
ln -sfn backend-laravel/public/js js
ln -sfn backend-laravel/public/storage storage

# .env
cp "$LARAVEL/.env.mysql" "$LARAVEL/.env"
# Editar DB_PASSWORD y APP_KEY si es necesario

# Dependencias
cd "$LARAVEL"
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan migrate --force
php artisan shield:generate --all
chmod -R 775 storage bootstrap/cache
```

### Paso 3: Crear deploy script y webhook

```bash
# El deploy script
cat > ~/deploy-sertecapp.sh << 'SCRIPT'
#!/bin/bash
PUBLIC_HTML="/home/u283281385/domains/demo.pendziuch.com/public_html"
LARAVEL_DIR="$PUBLIC_HTML/backend-laravel"
REPO="https://github.com/PENDZIUCH/SerTecApp.git"
BRANCH="development"
LOG="/tmp/sertecapp_deploy.log"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG"; }

REMOTE_SHA=$(git ls-remote "$REPO" "refs/heads/$BRANCH" 2>/dev/null | cut -f1)
LOCAL_SHA=$(cat "$LARAVEL_DIR/.deploy_sha" 2>/dev/null || echo "none")

if [ "$LOCAL_SHA" = "$REMOTE_SHA" ] && [ "${1:-}" != "--force" ] && [ ! -f /tmp/sertecapp_deploy_needed ]; then
    exit 0
fi

rm -f /tmp/sertecapp_deploy_needed
log "=== DEPLOY INICIADO ==="

TEMP=$(mktemp -d)
git clone --branch "$BRANCH" --depth 1 "$REPO" "$TEMP" >> "$LOG" 2>&1 || { log "ERROR: clone falló"; rm -rf "$TEMP"; exit 1; }

cp "$LARAVEL_DIR/.env" "$TEMP/backend-laravel/.env"

for dir in app bootstrap/app.php config database resources routes; do
    rm -rf "$LARAVEL_DIR/$dir"
    cp -r "$TEMP/backend-laravel/$dir" "$LARAVEL_DIR/$dir"
done

if ! diff -q "$LARAVEL_DIR/composer.lock" "$TEMP/backend-laravel/composer.lock" > /dev/null 2>&1; then
    cp "$TEMP/backend-laravel/composer.lock" "$LARAVEL_DIR/composer.lock"
    cp "$TEMP/backend-laravel/composer.json" "$LARAVEL_DIR/composer.json"
    cd "$LARAVEL_DIR" && /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction >> "$LOG" 2>&1
fi

rm -rf "$TEMP"
cd "$LARAVEL_DIR"
/usr/bin/php artisan migrate --force >> "$LOG" 2>&1
/usr/bin/php artisan optimize:clear >> "$LOG" 2>&1
chmod -R 775 storage bootstrap/cache 2>/dev/null
echo "$REMOTE_SHA" > "$LARAVEL_DIR/.deploy_sha"
log "=== DEPLOY COMPLETADO ==="
SCRIPT
chmod +x ~/deploy-sertecapp.sh
```

### Paso 4: Configurar auto-deploy

Decirle al usuario:
1. **GitHub Webhook** → https://github.com/PENDZIUCH/SerTecApp/settings/hooks/new
   - URL: `https://demo.pendziuch.com/deploy.php`
   - Secret: `SerTecDeploy2026!`
   - Event: push
2. **hPanel → Cron Jobs**: `/bin/bash /home/u283281385/deploy-sertecapp.sh` cada 5 min

### Paso 5: Verificar

Correr el modo `verify`.

---

## Diagnóstico — si algo falla

Siempre empezar habilitando debug temporalmente:

```bash
ssh -n -i "C:/Users/Hugo Pendziuch/.ssh/hostinger_sertecapp" -o StrictHostKeyChecking=no -p 65002 u283281385@147.79.103.125 \
  'sed -i "s/APP_DEBUG=false/APP_DEBUG=true/" ~/domains/demo.pendziuch.com/public_html/backend-laravel/.env'
```

Luego reproducir el error con curl y leer el mensaje real. Después apagar debug:

```bash
ssh -n -i "C:/Users/Hugo Pendziuch/.ssh/hostinger_sertecapp" -o StrictHostKeyChecking=no -p 65002 u283281385@147.79.103.125 \
  'sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" ~/domains/demo.pendziuch.com/public_html/backend-laravel/.env'
```

| Síntoma | Fix |
|---------|-----|
| 500 silencioso en web | `AddHandler application/x-httpd-php83 .php` en .htaccess |
| 403 en /admin | Cambiar path de Filament, nunca usar /admin |
| 403 post-login | Implementar `FilamentUser` con `canAccessPanel()` |
| Redirect / a /admin 404 | Fix `routes/web.php` |
| `IpUtils null` | TrustProxies con headers en `bootstrap/app.php` |
| `applyFilters()` 500 | Scope no definido, reemplazar con filtros manuales |
| git pull no actualiza | Usar deploy script con clone+copy, no git pull directo |
