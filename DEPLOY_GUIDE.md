# 🚀 GUÍA DE DEPLOY - SERTECAPP
## Para nunca más cometer los errores del 10 de diciembre 2025

---

## ⚠️ ERRORES COMETIDOS (NUNCA MÁS REPETIR)

### ERROR 1: Git Subtree Split roto
**Problema:** `git subtree split` no incluyó archivos críticos (artisan, composer.lock)  
**Consecuencia:** Deploy falló porque faltaban archivos esenciales  
**Lección:** NO usar subtree split. Crear rama limpia manualmente.

### ERROR 2: Deployar código viejo
**Problema:** Rama `backend-only` tenía código de hace días, no el actual con Excel import  
**Consecuencia:** Sistema deployado sin la funcionalidad principal  
**Lección:** SIEMPRE verificar qué código tiene la rama antes de deployar.

### ERROR 3: Estructura incompatible con Git
**Problema:** Mover archivos manualmente rompió el tracking de Git  
**Consecuencia:** 3 horas debugueando problemas de rutas  
**Lección:** Usar script automatizado para mover archivos.

### ERROR 4: No testear antes de confirmar
**Problema:** Dije "está listo" sin verificar funcionalidades  
**Consecuencia:** Pérdida de confianza del cliente  
**Lección:** Probar TODAS las features antes de confirmar.

### ERROR 5: Comandos sin verificar
**Problema:** `rm -rf` y `mv` sin verificar estructura primero  
**Consecuencia:** Archivos borrados y horas recuperando  
**Lección:** SIEMPRE `ls` primero, luego actuar.

### ERROR 6: No hay roles en BD
**Problema:** Migraciones corrieron pero no seeders  
**Consecuencia:** Usuarios sin permisos, botones invisibles  
**Lección:** Siempre crear roles después de migrate.

### ERROR 7: Notificaciones efímeras
**Problema:** Errores desaparecían en 3 segundos  
**Consecuencia:** Usuario no puede leer/analizar errores  
**Lección:** Notificaciones de error deben ser persistentes.

---

## ✅ PROCESO DE DEPLOY CORRECTO

### ANTES DE DEPLOYAR (Checklist obligatorio)

```bash
# 1. Verificar que estás en la rama correcta
cd C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel
git branch --show-current
# Debe decir: feature/excel-importer

# 2. Verificar que todos los cambios están commiteados
git status
# Debe decir: "nothing to commit, working tree clean"

# 3. Verificar que los archivos críticos existen
ls artisan
ls composer.json
ls composer.lock
ls app/Filament/Resources/CustomerResource/Pages/ListCustomers.php

# 4. Probar localmente
php artisan serve
# Abrir http://localhost:8000/admin
# Verificar que Import/Export funcionan

# 5. SOLO SI TODO ANDA: Pushear a backend-only
git push origin feature/excel-importer:backend-only --force
```

---

## 📦 DEPLOY A HOSTINGER (Paso a Paso)

### OPCIÓN A: Deploy Manual Limpio (Primera vez)

```bash
# 1. Conectar por SSH
ssh -p 65002 u283281385@147.79.103.125

# 2. Ir al directorio del sitio
cd /home/u283281385/domains/demos.pendziuch.com

# 3. Backup del .env (CRÍTICO)
cp .env /tmp/env_backup_$(date +%Y%m%d_%H%M%S).txt

# 4. Limpiar archivos viejos (MENOS public_html)
rm -rf app bootstrap config database resources routes storage tests vendor
rm -f artisan composer.json composer.lock phpunit.xml

# 5. Clonar código nuevo
git clone --branch backend-only --depth 1 https://github.com/PENDZIUCH/SerTecApp.git temp

# 6. Mover solo backend-laravel (sin la basura)
cd temp/backend-laravel
mv app bootstrap config database resources routes storage tests ../../
mv composer.json composer.lock phpunit.xml .env.example ../../
cd ../..
rm -rf temp

# 7. Restaurar .env
cp /tmp/env_backup_*.txt .env

# 8. Crear artisan si no existe
if [ ! -f artisan ]; then
cat > artisan << 'EOF'
#!/usr/bin/env php
<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);
$kernel->terminate($input, $status);
exit($status);
EOF
chmod +x artisan
fi

# 9. Instalar dependencias
composer install --no-dev --optimize-autoloader

# 10. Permisos
chmod -R 775 storage bootstrap/cache

# 11. Limpiar cache
php artisan optimize:clear

# 12. Verificar roles (CRÍTICO)
php artisan tinker --execute="echo 'Roles: ' . \App\Models\Role::count();"
# Si da 0, crear roles:
mysql -u u283281385_sertecapp_lara -p'Vida-2026' u283281385_sertecapp_lara << 'EOF'
INSERT IGNORE INTO roles (name, guard_name, created_at, updated_at) VALUES 
('admin', 'web', NOW(), NOW()),
('technician', 'web', NOW(), NOW()),
('viewer', 'web', NOW(), NOW());
EOF

# 13. Asignar rol admin al usuario
mysql -u u283281385_sertecapp_lara -p'Vida-2026' u283281385_sertecapp_lara << 'EOF'
INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
VALUES (1, 'App\\Models\\User', (SELECT id FROM users WHERE email='pendziuch@gmail.com'));
EOF

# 14. Test final
curl -I https://demos.pendziuch.com/admin
# Debe responder 200 o 302 (redirect a login)
```

### OPCIÓN B: Actualización Rápida (código ya deployado)

```bash
# 1. SSH
ssh -p 65002 u283281385@147.79.103.125

# 2. Ir al directorio
cd /home/u283281385/domains/demos.pendziuch.com

# 3. Si hay un archivo específico que cambió (ej: ListCustomers.php)
wget -O app/Filament/Resources/CustomerResource/Pages/ListCustomers.php.new \
  "https://raw.githubusercontent.com/PENDZIUCH/SerTecApp/backend-only/backend-laravel/app/Filament/Resources/CustomerResource/Pages/ListCustomers.php"
mv app/Filament/Resources/CustomerResource/Pages/ListCustomers.php.new \
   app/Filament/Resources/CustomerResource/Pages/ListCustomers.php

# 4. Limpiar cache
php artisan optimize:clear

# 5. Test
curl -I https://demos.pendziuch.com/admin
```

---

## 🔧 PROBLEMAS COMUNES Y SOLUCIONES

### Problema: "Could not open input file: artisan"
**Causa:** Archivo `artisan` no existe  
**Solución:**
```bash
cat > artisan << 'EOF'
#!/usr/bin/env php
<?php
define('LARAVEL_START', microtime(true));
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$status = $kernel->handle(
    $input = new Symfony\Component\Console\Input\ArgvInput,
    new Symfony\Component\Console\Output\ConsoleOutput
);
$kernel->terminate($input, $status);
exit($status);
EOF
chmod +x artisan
```

### Problema: No veo botones Import/Export
**Causa:** Usuario no tiene rol 'admin'  
**Solución:**
```bash
# Verificar roles existentes
mysql -u u283281385_sertecapp_lara -p'Vida-2026' u283281385_sertecapp_lara -e "SELECT * FROM roles;"

# Si no hay roles, crearlos
mysql -u u283281385_sertecapp_lara -p'Vida-2026' u283281385_sertecapp_lara << 'EOF'
INSERT IGNORE INTO roles (name, guard_name, created_at, updated_at) VALUES 
('admin', 'web', NOW(), NOW()),
('technician', 'web', NOW(), NOW()),
('viewer', 'web', NOW(), NOW());
EOF

# Asignar rol admin
mysql -u u283281385_sertecapp_lara -p'Vida-2026' u283281385_sertecapp_lara << 'EOF'
INSERT IGNORE INTO model_has_roles (role_id, model_type, model_id)
VALUES (1, 'App\\Models\\User', (SELECT id FROM users WHERE email='TU_EMAIL@gmail.com'));
EOF
```

### Problema: Error 500 Internal Server Error
**Causa:** Permisos incorrectos en storage/  
**Solución:**
```bash
cd /home/u283281385/domains/demos.pendziuch.com
chmod -R 775 storage bootstrap/cache
chown -R u283281385:o1006714258 storage bootstrap/cache
```

### Problema: Error de migraciones
**Causa:** Tabla `visits` tiene foreign key a `subscriptions` inexistente  
**Solución:**
```bash
# Editar migración
nano database/migrations/2024_01_01_000028_create_visits_table.php
# Comentar o eliminar línea: $table->foreignId('subscription_id')...
# Y también: $table->index('subscription_id');

# Reintentar
mysql -u u283281385_sertecapp_lara -p'Vida-2026' u283281385_sertecapp_lara -e "DROP TABLE IF EXISTS visits;"
php artisan migrate --force
```

### Problema: composer.json not found
**Causa:** Archivos no se movieron correctamente  
**Solución:**
```bash
# Verificar estructura
ls -la /home/u283281385/domains/demos.pendziuch.com/
# Debe tener: app/, bootstrap/, config/, composer.json, artisan, etc.

# Si faltan, reclonar
cd /home/u283281385/domains/demos.pendziuch.com
git clone --branch backend-only --depth 1 https://github.com/PENDZIUCH/SerTecApp.git temp
mv temp/backend-laravel/* ./
rm -rf temp
```

---

## 📊 TESTING CHECKLIST POST-DEPLOY

Después de cada deploy, verificar:

```bash
# 1. Sitio responde
curl -I https://demos.pendziuch.com/admin
# Esperar: 200 o 302

# 2. Login funciona
# Ir a https://demos.pendziuch.com/admin/login
# Logear con credenciales

# 3. Dashboard carga
# Ver https://demos.pendziuch.com/admin
# Debe mostrar widgets

# 4. Clientes lista
# Ir a https://demos.pendziuch.com/admin/customers
# Debe mostrar tabla (vacía está ok)

# 5. Botones visibles (si admin)
# Debe ver:
# - Botón verde "Crear Cliente"
# - Botón azul "Exportar Excel"
# - Botón amarillo "Importar Excel/CSV"
# - Botón rojo "Eliminar Todos"

# 6. Import funciona
# Click "Importar Excel/CSV"
# Upload archivo
# Debe mostrar notificación PERSISTENTE con resultados

# 7. Export funciona
# Click "Exportar Excel"
# Debe descargar archivo .xlsx
```

---

## 🎯 REGLAS DE ORO

1. **NUNCA deployar sin testear localmente primero**
2. **NUNCA hacer `rm -rf` sin verificar `pwd` primero**
3. **SIEMPRE hacer backup de .env antes de cambios**
4. **SIEMPRE verificar rama con `git branch --show-current`**
5. **SIEMPRE probar el sitio después del deploy**
6. **SIEMPRE crear roles después de migrate**
7. **SIEMPRE usar `--force` en comandos artisan en producción**
8. **NUNCA asumir que "debería funcionar"**
9. **SIEMPRE leer el error completo antes de actuar**
10. **SIEMPRE documentar problemas nuevos en esta guía**

---

## 📝 LOG DE CAMBIOS

### 2025-12-10: Deploy inicial caótico
- **Problema:** Todo lo documentado arriba
- **Solución:** Esta guía
- **Tiempo perdido:** 3 horas
- **Lección:** Nunca más improvisar

### 2025-12-11: Fix notificaciones persistentes
- **Problema:** Errores desaparecían en 3s
- **Solución:** Agregado `->persistent()` a notificaciones
- **Commit:** f396f63
- **Deploy:** Actualización rápida (Opción B)
- **Tiempo:** 5 minutos

---

## 🚀 PRÓXIMA MEJORA: SCRIPT DE DEPLOY AUTOMÁTICO

Crear archivo `deploy.sh` en repo:

```bash
#!/bin/bash
# TODO: Implementar script que haga todo automáticamente
# - Verificar código localmente
# - Pushear a backend-only
# - SSH a servidor
# - Actualizar código
# - Limpiar cache
# - Test
# - Notificar resultado
```

---

**FIN DE LA GUÍA**

**Última actualización:** 2025-12-11 01:15 AM  
**Autor:** Claude (que la cagó y aprendió)  
**Revisar esta guía ANTES de cada deploy**
