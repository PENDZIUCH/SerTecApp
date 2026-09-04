#!/bin/bash

# ============================================
# SerTecApp - Deploy Script para Hostinger
# Rama: development (Filament completo)
# ============================================

set -e  # Exit on any error

# CONFIGURACIÓN
DOMAIN="demos.pendziuch.com"
SSH_USER="u283281385"
REMOTE_PATH="/home/u283281385/domains/${DOMAIN}"
PUBLIC_HTML="${REMOTE_PATH}/public_html"
BRANCH="development"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}🚀 DEPLOY SERTECAPP - ${DOMAIN}${NC}"
echo -e "${BLUE}========================================${NC}"

# ============ PRE-DEPLOY CHECKS ============
echo -e "\n${YELLOW}📋 Verificaciones pre-deploy...${NC}"

# 1. Verificar rama local
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" != "$BRANCH" ]; then
    echo -e "${RED}❌ ERROR: Estás en rama '$CURRENT_BRANCH', debes estar en '$BRANCH'${NC}"
    echo -e "   Cambiar: ${YELLOW}git checkout $BRANCH${NC}"
    exit 1
fi

# 2. Verificar cambios sin commitear
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}❌ ERROR: Hay cambios sin commitear${NC}"
    git status
    echo -e "   Commitea primero: ${YELLOW}git add . && git commit -m 'Mensaje'${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Rama correcta ($BRANCH) y limpia${NC}"

# ============ PUSH A GITHUB ============
echo -e "\n${YELLOW}📤 Pusheando a GitHub...${NC}"
git push origin $BRANCH

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ ERROR: Push a GitHub falló${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Código en GitHub${NC}"

# ============ DEPLOY EN HOSTINGER ============
echo -e "\n${YELLOW}🔧 Deployando en Hostinger...${NC}"

ssh ${SSH_USER}@147.79.103.125 -p 65002 bash << 'DEPLOY_SCRIPT'

set -e

DOMAIN="demos.pendziuch.com"
REMOTE_PATH="/home/u283281385/domains/${DOMAIN}"
PUBLIC_HTML="${REMOTE_PATH}/public_html"
BRANCH="development"

cd "$PUBLIC_HTML"

echo -e "${YELLOW}📥 Git pull...${NC}"
git pull origin $BRANCH

if [ ! -d "vendor" ]; then
    echo -e "${YELLOW}📦 Primer deploy: Composer install...${NC}"
    composer install --no-dev --optimize-autoloader --no-interaction
else
    echo -e "${YELLOW}📦 Actualizando dependencias...${NC}"
    composer update --no-dev --optimize-autoloader --no-interaction
fi

echo -e "${YELLOW}📊 Ejecutar migraciones...${NC}"
php artisan migrate --force

echo -e "${YELLOW}🧹 Limpiar cache...${NC}"
php artisan config:cache
php artisan cache:clear
php artisan optimize:clear

echo -e "${YELLOW}🔐 Configurar permisos...${NC}"
chmod -R 775 storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;

echo -e "${GREEN}✅ Deploy completado${NC}"

DEPLOY_SCRIPT

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ ERROR: Deploy en Hostinger falló${NC}"
    exit 1
fi

# ============ POST-DEPLOY VERIFICATION ============
echo -e "\n${YELLOW}🔍 Verificando deploy...${NC}"

# Esperar 2 segundos para que servidor se estabilice
sleep 2

HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" https://${DOMAIN}/admin/login)

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    echo -e "${GREEN}✅ Sitio respondiendo correctamente (HTTP $HTTP_CODE)${NC}"
else
    echo -e "${RED}⚠️  Sitio responde con HTTP $HTTP_CODE${NC}"
    echo -e "   Revisar logs: ${YELLOW}ssh -p 65002 ${SSH_USER}@147.79.103.125${NC}"
    echo -e "   Luego: ${YELLOW}tail -f ${REMOTE_PATH}/storage/logs/laravel.log${NC}"
fi

# ============ RESUMEN ============
echo ""
echo -e "${BLUE}========================================${NC}"
echo -e "${GREEN}🎉 DEPLOY COMPLETADO EXITOSAMENTE${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""
echo -e "${BLUE}📋 Verificaciones manuales:${NC}"
echo "   1. Abre: https://${DOMAIN}/admin/login"
echo "   2. Intenta login: admin@sertecapp.local / password"
echo "   3. Verifica Filament carga correctamente"
echo "   4. Chequea usuarios/roles en admin"
echo ""
echo -e "${BLUE}📊 URL importante:${NC}"
echo "   Admin: https://${DOMAIN}/admin"
echo "   API: https://${DOMAIN}/api"
echo ""
echo -e "${BLUE}📝 Logs en vivo:${NC}"
echo "   ssh -p 65002 ${SSH_USER}@147.79.103.125"
echo "   tail -f ${REMOTE_PATH}/storage/logs/laravel.log"
echo ""
