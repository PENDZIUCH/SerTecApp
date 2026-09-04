#!/bin/bash
# =============================================================
# SertecCore — Install Script v1.0
# Pendziuch Labs — https://pendziuch.com
# =============================================================
# Uso:
#   bash install.sh \
#     --client="HVAC Demo" \
#     --domain="hvac.app.pendziuch.com" \
#     --email="admin@hvac.com" \
#     --db-name="u283281385_hvac" \
#     --db-user="u283281385_hvac" \
#     --db-pass="PASS" \
#     --mail-user="mail@pendziuch.com" \
#     --mail-pass="PASS" \
#     --modules="customers,work_orders,equipment,parts"
#
# PREREQUISITOS (hacer en Hostinger hPanel antes de correr):
#   1. Crear addon domain o subdominio
#   2. Crear base de datos MySQL
#   3. Tener cuenta de email SMTP disponible
# =============================================================

set -e

# --- Colores ---
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok()   { echo -e "[OK] \"; }
info() { echo -e "[..] \"; }
fail() { echo -e "[ERR] \"; exit 1; }

# --- Parsear argumentos ---
for arg in "\$@"; do
  case \ in
    --client=*)    CLIENT_NAME="\" ;;
    --domain=*)    DOMAIN="\" ;;
    --email=*)     ADMIN_EMAIL="\" ;;
    --db-name=*)   DB_NAME="\" ;;
    --db-user=*)   DB_USER="\" ;;
    --db-pass=*)   DB_PASS="\" ;;
    --mail-user=*) MAIL_USER="\" ;;
    --mail-pass=*) MAIL_PASS="\" ;;
    --modules=*)   MODULES="\" ;;
  esac
done

# --- Validar ---
[[ -z "\" ]] && fail "--client es requerido"
[[ -z "\" ]]      && fail "--domain es requerido"
[[ -z "\" ]] && fail "--email es requerido"
[[ -z "\" ]]     && fail "--db-name es requerido"
[[ -z "\" ]]     && fail "--db-user es requerido"
[[ -z "\" ]]     && fail "--db-pass es requerido"

MODULES=\
REPO="https://github.com/PENDZIUCH/SerTecApp.git"
BRANCH="core/v1"
INSTALL_DIR="/home/u283281385/domains/\/public_html"
TEMP_DIR="/home/u283281385/tmp/serteccore_install"
ADMIN_PASS=\

echo ""
echo "======================================================"
echo "  SertecCore Installer — Pendziuch Labs"
echo "======================================================"
echo "  Cliente:  \"
echo "  Dominio:  \"
echo "  Admin:    \"
echo "  Módulos:  \"
echo "======================================================"
echo ""

# --- 1. Clonar repo ---
info "Clonando core/v1 del repositorio..."
rm -rf "\"
git clone --branch "\" --depth 1 "\" "\" || fail "No se pudo clonar el repo"
ok "Repo clonado"

# --- 2. Copiar backend al directorio del dominio ---
info "Copiando archivos al directorio del dominio..."
mkdir -p "\"
cp -r "\/backend-laravel/." "\/"
rm -rf "\"
ok "Archivos copiados a \"

# --- 3. Configurar .env ---
info "Configurando .env..."
cp "\/.env.example" "\/.env" 2>/dev/null || \
  cp "\/.env.template" "\/.env"

sed -i "s|__CLIENT_NAME__||g" "\/.env"
sed -i "s|__DOMAIN__||g"           "\/.env"
sed -i "s|__DB_NAME__||g"         "\/.env"
sed -i "s|__DB_USER__||g"         "\/.env"
sed -i "s|__DB_PASS__||g"         "\/.env"
sed -i "s|__MAIL_USER__||g" "\/.env"
sed -i "s|__MAIL_PASS__||g" "\/.env"
ok ".env configurado"

# --- 4. Composer ---
info "Instalando dependencias PHP..."
cd "\"
composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3
ok "Composer OK"

# --- 5. Laravel setup ---
info "Generando APP_KEY..."
php artisan key:generate --force
ok "APP_KEY generada"

info "Corriendo migraciones..."
php artisan migrate --force
ok "Migraciones OK"

info "Optimizando..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
ok "Cache OK"

# --- 6. Roles ---
info "Creando roles..."
php artisan tinker --execute="
  \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
  \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
  \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'técnico', 'guard_name' => 'web']);
  echo 'roles OK';
"
ok "Roles creados"

# --- 7. Super admin ---
info "Creando super_admin..."
php artisan tinker --execute="
  \ = \App\Models\User::firstOrCreate(
    ['email' => ''],
    ['name' => 'Administrador', 'password' => bcrypt('')]
  );
  \->assignRole('super_admin');
  echo 'super_admin OK';
"
ok "Super admin creado"

# --- 8. Módulos ---
info "Activando módulos: \..."
php artisan tinker --execute="
  \ = ['customers','work_orders','visits','budgets','parts','workshop','subscriptions','equipment'];
  \ = explode(',', '');
  \ = [];
  foreach (\ as \) { \[\] = in_array(\, \); }
  \App\Models\SystemSetting::set('active_modules', json_encode(\));
  echo 'módulos OK';
"
ok "Módulos configurados"

# --- Resumen ---
echo ""
echo "======================================================"
echo -e "  INSTALACIÓN COMPLETA"
echo "======================================================"
echo "  URL Admin:   https://\/sertecapp"
echo "  Email:       \"
echo "  Contraseña:  \"
echo "  Módulos:     \"
echo ""
echo "  IMPORTANTE: Cambiar la contraseña al primer ingreso"
echo "======================================================"
echo ""