# 🚀 PROCESO DE DEPLOY PROFESIONAL - SERTECAPP
## Script automatizado para Hostinger

---

## 📋 ESTRUCTURA HOSTINGER (OBLIGATORIA)

```
/home/u283281385/domains/SUBDOMINIO.pendziuch.com/
├── app/                    # Laravel app
├── bootstrap/              # Laravel bootstrap  
├── config/                 # Configuración
├── database/               # Migraciones + seeders
├── resources/              # Views, assets
├── routes/                 # Rutas
├── storage/                # Logs, cache, uploads
├── vendor/                 # Dependencias Composer
├── artisan                 # CLI Laravel
├── composer.json           # Dependencias
├── composer.lock           # Lock file
├── .env                    # Config (DB, app key) - NUNCA en Git
└── public_html/            # ← DOCUMENT ROOT (dominio apunta acá)
    ├── index.php           # Entry point Laravel
    ├── .htaccess           # Apache rules
    ├── favicon.ico
    ├── robots.txt
    └── (assets compilados)
```

**CRÍTICO:** Todo Laravel FUERA de public_html, solo contenido de `public/` DENTRO.

---

## 🔧 SCRIPT DE DEPLOY AUTOMATIZADO

### deploy-to-hostinger.sh

```bash
#!/bin/bash

# CONFIGURACIÓN
DOMAIN="dev.pendziuch.com"  # Cambiar según subdominio
SSH_USER="u283281385"
SSH_HOST="147.79.103.125"
SSH_PORT="65002"
REMOTE_PATH="/home/u283281385/domains/${DOMAIN}"
BRANCH="backend-only"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 DEPLOY SERTECAPP - ${DOMAIN}${NC}"
echo "================================================"

# 1. VERIFICACIONES PRE-DEPLOY
echo -e "\n${YELLOW}📋 Verificaciones pre-deploy...${NC}"

# Verificar rama
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "feature/excel-importer" ]; then
    echo -e "${RED}❌ ERROR: Debes estar en rama feature/excel-importer${NC}"
    exit 1
fi

# Verificar que no haya cambios sin commitear
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}❌ ERROR: Hay cambios sin commitear${NC}"
    git status
    exit 1
fi

echo -e "${GREEN}✅ Rama correcta y limpia${NC}"

# 2. ACTUALIZAR RAMA BACKEND-ONLY
echo -e "\n${YELLOW}📤 Actualizando rama backend-only...${NC}"
git push origin feature/excel-importer:backend-only --force

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ ERROR: Falló push a GitHub${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Rama backend-only actualizada en GitHub${NC}"

# 3. BACKUP REMOTO
echo -e "\n${YELLOW}💾 Creando backup en servidor...${NC}"

ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST} << 'ENDSSH'
cd /home/u283281385/domains/${DOMAIN}
if [ -f .env ]; then
    BACKUP_DATE=$(date +%Y%m%d_%H%M%S)
    cp .env /tmp/env_backup_${BACKUP_DATE}.txt
    echo "✅ Backup .env creado: /tmp/env_backup_${BACKUP_DATE}.txt"
else
    echo "⚠️  No existe .env (primera vez?)"
fi
ENDSSH

# 4. DEPLOY EN SERVIDOR
echo -e "\n${YELLOW}🔧 Deployando en servidor...${NC}"

ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST} << ENDSSH
set -e  # Exit on error

cd ${REMOTE_PATH}

echo "📥 Clonando código desde GitHub..."
if [ -d "temp" ]; then rm -rf temp; fi
git clone --branch ${BRANCH} --depth 1 https://github.com/PENDZIUCH/SerTecApp.git temp

echo "📂 Moviendo archivos Laravel..."
cd temp/backend-laravel

# Mover archivos Laravel a directorio padre
for item in app bootstrap config database resources routes storage tests vendor artisan composer.json composer.lock phpunit.xml; do
    if [ -e "\$item" ]; then
        echo "  → Moviendo \$item"
        mv "\$item" ../../
    fi
done

cd ../..

# Mover contenido public/ a public_html/
echo "📂 Actualizando public_html..."
if [ -d "temp/backend-laravel/public" ]; then
    # Backup de public_html actual
    if [ -d "public_html_backup" ]; then rm -rf public_html_backup; fi
    if [ -d "public_html" ]; then
        mv public_html public_html_backup
    fi
    
    # Mover nuevo public
    mv temp/backend-laravel/public public_html
fi

# Limpiar
rm -rf temp

echo "✅ Archivos deployados"

# Restaurar .env si existe backup
if [ -f /tmp/env_backup_*.txt ]; then
    LATEST_BACKUP=\$(ls -t /tmp/env_backup_*.txt | head -1)
    cp "\$LATEST_BACKUP" .env
    echo "✅ .env restaurado desde backup"
fi

# Crear .env si no existe (primera vez)
if [ ! -f .env ]; then
    echo "🔧 Creando .env inicial..."
    cp .env.example .env
    php artisan key:generate --force
    
    echo ""
    echo "⚠️  IMPORTANTE: Configurar .env manualmente:"
    echo "   - DB_DATABASE"
    echo "   - DB_USERNAME"  
    echo "   - DB_PASSWORD"
    echo ""
fi

# Verificar artisan existe
if [ ! -f artisan ]; then
    echo "🔧 Creando artisan..."
    cat > artisan << 'ARTISAN'
#!/usr/bin/env php
<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
\$app = require_once __DIR__.'/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$status = \$kernel->handle(
    \$input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);
\$kernel->terminate(\$input, \$status);
exit(\$status);
ARTISAN
    chmod +x artisan
fi

# Composer install
echo "📦 Instalando dependencias..."
composer install --no-dev --optimize-autoloader --no-interaction

# Permisos
echo "🔐 Configurando permisos..."
chmod -R 775 storage bootstrap/cache

# Limpiar cache
echo "🧹 Limpiando cache..."
php artisan optimize:clear

echo ""
echo "✅ Deploy completado exitosamente"
echo ""

ENDSSH

# 5. VERIFICACIÓN POST-DEPLOY
echo -e "\n${YELLOW}🔍 Verificando deploy...${NC}"

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN}/admin)

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo -e "${GREEN}✅ Sitio respondiendo correctamente (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}⚠️  Sitio responde con HTTP $HTTP_CODE (revisar)${NC}"
fi

# 6. RESUMEN
echo ""
echo "================================================"
echo -e "${GREEN}🎉 DEPLOY COMPLETADO${NC}"
echo ""
echo "🌐 URL: https://${DOMAIN}/admin"
echo "📝 Verificar manualmente:"
echo "   1. Login funciona"
echo "   2. Dashboard carga"
echo "   3. Módulos visibles (Clientes, Usuarios)"
echo "   4. Import/Export Excel"
echo ""
echo "📋 Logs en servidor:"
echo "   ssh -p $SSH_PORT ${SSH_USER}@${SSH_HOST}"
echo "   tail -f ${REMOTE_PATH}/storage/logs/laravel.log"
echo ""
```

---

## 📝 USO DEL SCRIPT

### Primera vez (crear subdominio):

1. **En Hostinger Panel:**
   - Crear subdominio: `dev.pendziuch.com`
   - Apuntar a: `/home/u283281385/domains/dev.pendziuch.com/public_html`

2. **Crear base de datos:**
   ```sql
   Database: u283281385_sertec_dev
   Username: u283281385_sertec_dev
   Password: [generar]
   ```

3. **Ejecutar deploy:**
   ```bash
   chmod +x deploy-to-hostinger.sh
   ./deploy-to-hostinger.sh
   ```

4. **Configurar .env por SSH:**
   ```bash
   ssh -p 65002 u283281385@147.79.103.125
   cd /home/u283281385/domains/dev.pendziuch.com
   nano .env
   # Configurar DB_*
   php artisan migrate --force --seed
   ```

### Deploy subsiguientes:

```bash
./deploy-to-hostinger.sh
```

**¡Eso es todo!** El script hace todo automáticamente.

---

## 🔄 FLUJO COMPLETO DE DESARROLLO

```
1. Desarrollo LOCAL
   ↓
2. Commit y push a feature/excel-importer
   ↓
3. ./deploy-to-hostinger.sh (push a backend-only + deploy a dev)
   ↓
4. Testing en dev.pendziuch.com
   ↓
5. Si OK → cambiar DOMAIN="demos.pendziuch.com" y re-deployar
   ↓
6. Producción actualizada ✅
```

---

## ⚠️ TROUBLESHOOTING

### Problema: "composer.json not found"
**Causa:** Archivos no se movieron correctamente  
**Solución:**
```bash
ssh -p 65002 u283281385@147.79.103.125
cd /home/u283281385/domains/SUBDOMINIO/
ls -la  # Verificar que están: app/, config/, composer.json
```

### Problema: "Could not open input file: artisan"
**Causa:** Archivo artisan no existe  
**Solución:** El script lo crea automáticamente, o crear manualmente con contenido del script

### Problema: HTTP 500
**Causa:** Permisos incorrectos o .env mal configurado  
**Solución:**
```bash
chmod -R 775 storage bootstrap/cache
php artisan config:clear
tail -f storage/logs/laravel.log
```

### Problema: "Table not found"
**Causa:** Migraciones no corrieron  
**Solución:**
```bash
php artisan migrate --force
php artisan db:seed --force  # Si necesita datos iniciales
```

---

## 📊 CHECKLIST POST-DEPLOY

```
□ URL responde (200 o 302)
□ Login funciona
□ Dashboard carga
□ Módulo Clientes visible
□ Módulo Usuarios visible (solo admin)
□ Import Excel funciona
□ Export Excel funciona
□ Crear cliente funciona
□ Validaciones funcionan (email único, etc)
□ Editar cliente funciona
□ Redirect después de crear/editar
□ Roles y permisos funcionan
```

---

## 🎯 VENTAJAS DE ESTE PROCESO

✅ **Reproducible:** Mismo comando siempre  
✅ **Seguro:** Backup automático de .env  
✅ **Rápido:** 2 minutos vs 30 minutos manual  
✅ **Sin errores:** No más olvidar pasos  
✅ **Testeable:** Deploy a staging primero  
✅ **Rollback fácil:** Backup de .env disponible  

---

**Última actualización:** 2025-12-11 16:00  
**Testeado en:** desarrollo local  
**Próximo:** testear en dev.pendziuch.com  
