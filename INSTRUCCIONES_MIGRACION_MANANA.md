# 📋 Instrucciones - Migración SQLite → MySQL (Mañana)

**Fecha:** 2026-04-09  
**Objetivo:** Cambiar el admin de SQLite a MySQL localmente en UN PASO

---

## ✅ YA PREPARADO HOY

- ✅ `.env.mysql.local` — configuración MySQL lista
- ✅ `MIGRATE_SQLITE_TO_MYSQL.ps1` — script de migración automático
- ✅ Todos los modelos y migraciones listos
- ✅ Endpoints API funcionando

## 📋 QUÉ HACER MAÑANA

### Step 1: Testear Excel Importer/Exporter (PRIMERO)

Antes de migrar, confirmar que el Excel importer/exporter funciona en el admin actual:

```bash
# 1. Levantar Laravel (si no está corriendo)
cd C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel
php artisan serve --host=127.0.0.1 --port=8000

# 2. Acceder a admin
# URL: http://localhost:8000/admin

# 3. Testear:
#    - Importar Excel de clientes
#    - Exportar Excel de clientes
#    - Verificar que funciona sin errores
```

**Si todo anda:** Proceder a Step 2.  
**Si hay error:** Revisar backend-only rama para recuperar utilities.

---

### Step 2: Ejecutar Script de Migración

Una vez confirmado que Excel funciona:

```powershell
# IMPORTANTE: Ejecutar COMO ADMINISTRADOR

powershell -ExecutionPolicy Bypass -File "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\MIGRATE_SQLITE_TO_MYSQL.ps1"
```

**Qué hace el script:**
1. Verifica que MySQL está disponible (Laragon)
2. Crea base de datos `sertecapp` en MySQL
3. Ejecuta `php artisan migrate --force` (crea todas las tablas)
4. Ejecuta seeders (si existen)
5. Verifica que se crearon las tablas
6. **AUTOMÁTICAMENTE** cambia `.env` a MySQL

**Resultado esperado:**
```
========================================
  ✅ MIGRACION COMPLETADA
========================================
```

---

### Step 3: Levantar Admin con MySQL

Después del script, levantar Laravel de nuevo:

```bash
cd C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel
php artisan serve --host=127.0.0.1 --port=8000
```

**Testear:**
- ✅ Admin abre en http://localhost:8000/admin
- ✅ Puedo logearme
- ✅ Listar clientes (desde MySQL)
- ✅ Crear un cliente de prueba
- ✅ Ver que se guardó en MySQL
- ✅ Excel importer/exporter sigue funcionando

---

### Step 4: Verificar BD MySQL

Si quieres verificar qué se creó en MySQL:

```bash
# Desde terminal o HeidiSQL
mysql -u root

# Dentro de MySQL:
USE sertecapp;
SHOW TABLES;  # Deberías ver ~40 tablas
SELECT COUNT(*) FROM users;  # Al menos 1 admin
```

---

## 🔄 Si algo falla

### Volver a SQLite

```bash
# El script creó un backup automático
copy "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel\.env.sqlite.backup" "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel\.env"

# Reiniciar Laravel
# Ya estaría de nuevo en SQLite
```

### Limpiar y reintentar

```bash
# Si necesitas limpiar todo y reintentar:
mysql -u root -e "DROP DATABASE sertecapp;"

# Y volver a correr el script
```

---

## 📝 Notas Importantes

1. **El script crea backup:** `.env.sqlite.backup` — puedes volver atrás en cualquier momento
2. **Las migraciones son idempotentes:** Puedes correr el script varias veces sin problema
3. **Los datos de admin se crean con seeders:** Usuarior default es `admin@sertecapp.com` / `admin123`
4. **MySQL debe estar en Laragon:** Si lo tienes en otro lado, necesitas ajustar rutas

---

## ✅ Checklist para mañana

- [ ] Testear Excel importer/exporter en SQLite
- [ ] Ejecutar `MIGRATE_SQLITE_TO_MYSQL.ps1`
- [ ] Levantar admin con MySQL
- [ ] Logearme en admin
- [ ] Listar y crear clientes
- [ ] Testear Excel importer/exporter con MySQL
- [ ] Verificar BD con MySQL CLI

---

**Una vez todo esté OK:** Pasamos al Step 1 del flujo de validación (base de datos en nube).

¿Preguntas antes de mañana? Revisa este archivo.
