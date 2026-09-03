# Base de Datos SerTecApp - Exportación Completa

## 📋 Información del Dump

**Archivo:** `sertecapp_complete.sql`  
**Base de datos:** `sertecapp`  
**Servidor:** MySQL 8.4.3  
**Charset:** utf8mb4  
**Fecha exportación:** 2025-11-21  
**Tamaño:** 29 KB  

---

## 📊 Contenido

### Tablas (13 total):
1. **abonos** - Contratos de mantenimiento mensual
2. **clientes** - Clientes (abonados y esporádicos)
3. **config_frecuencias** - Configuración colores por frecuencia
4. **configuracion_app** - Settings de la aplicación
5. **factura_items** - Items de facturas
6. **facturas** - Facturas emitidas
7. **orden_repuestos** - Repuestos usados en órdenes
8. **ordenes_trabajo** - Partes de trabajo técnico
9. **repuestos** - Inventario de repuestos
10. **sync_log** - Log de sincronización offline
11. **taller_equipos** - Equipos en taller/reparación
12. **usuarios** - Usuarios del sistema
13. **visitas_abono** - Registro de visitas mensuales

### Vistas (2 total):
1. **v_clientes_abonados** - Vista de clientes con abonos activos
2. **v_stock_bajo** - Repuestos con stock bajo mínimo

---

## 🚀 Importar en Windows (Laragon/XAMPP)

### Opción 1: MySQL Command Line
```bash
# Laragon
C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -u root -p

# Dentro de MySQL:
CREATE DATABASE IF NOT EXISTS sertecapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sertecapp;
SOURCE C:/Users/Hugo Pendziuch/Documents/claude/SerTecApp/database/sertecapp_complete.sql;
```

### Opción 2: HeidiSQL (GUI)
1. Abrir HeidiSQL (desde Laragon)
2. Conectar a MySQL
3. Crear BD: `sertecapp`
4. File → Run SQL file
5. Seleccionar: `sertecapp_complete.sql`
6. Ejecutar

### Opción 3: phpMyAdmin
1. Abrir phpMyAdmin
2. Crear base de datos: `sertecapp`
3. Seleccionar la BD
4. Pestaña "Importar"
5. Elegir archivo: `sertecapp_complete.sql`
6. Ejecutar

---

## 🍎 Importar en Mac

### Opción 1: Terminal
```bash
# Con MySQL instalado localmente
mysql -u root -p

# Dentro de MySQL:
CREATE DATABASE IF NOT EXISTS sertecapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sertecapp;
SOURCE /path/to/sertecapp_complete.sql;
```

### Opción 2: Una sola línea
```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS sertecapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p sertecapp < /path/to/sertecapp_complete.sql
```

### Opción 3: Con MAMP
```bash
/Applications/MAMP/Library/bin/mysql -u root -p sertecapp < /path/to/sertecapp_complete.sql
```

---

## 🐧 Importar en Linux

```bash
# Crear BD
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS sertecapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar
mysql -u root -p sertecapp < /path/to/sertecapp_complete.sql
```

---

## ☁️ Importar en Railway/Render

### Railway (via CLI)
```bash
# Obtener DATABASE_URL de Railway dashboard
export DATABASE_URL="mysql://user:pass@host:port/dbname"

# Importar
mysql $DATABASE_URL < sertecapp_complete.sql
```

### Render
1. Ir a Dashboard → Database
2. Connect → External Connection
3. Copiar credenciales
4. Usar comando mysql con las credenciales

---

## ✅ Verificar Importación

```sql
-- Conectar a la base
USE sertecapp;

-- Verificar tablas
SHOW TABLES;
-- Debe mostrar 15 (13 tablas + 2 vistas)

-- Verificar estructura clientes
DESCRIBE clientes;

-- Verificar usuarios (debe tener al menos 1)
SELECT * FROM usuarios;

-- Verificar config de colores
SELECT * FROM config_frecuencias;
```

---

## 🔐 Usuario por Defecto

**Email:** `admin@sertecapp.com`  
**Password:** `admin123` (hash bcrypt en BD)  
**Rol:** `admin`

⚠️ **IMPORTANTE:** Cambiar password en producción

```sql
-- Cambiar password del admin
UPDATE usuarios 
SET password_hash = '$2y$10$NUEVO_HASH_AQUI' 
WHERE email = 'admin@sertecapp.com';
```

Generar hash PHP:
```php
echo password_hash('nueva_password', PASSWORD_BCRYPT);
```

---

## 🔄 Re-exportar Base de Datos

### Windows (Laragon)
```bash
C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe -u root --default-character-set=utf8mb4 --result-file="sertecapp_backup.sql" sertecapp
```

### Mac/Linux
```bash
mysqldump -u root -p --default-character-set=utf8mb4 sertecapp > sertecapp_backup.sql
```

### Con estructura y datos
```bash
mysqldump -u root -p --default-character-set=utf8mb4 --complete-insert sertecapp > sertecapp_full.sql
```

### Solo estructura (sin datos)
```bash
mysqldump -u root -p --no-data sertecapp > sertecapp_schema_only.sql
```

---

## 📝 Notas Importantes

1. **Charset:** La BD usa `utf8mb4` para soportar emojis y caracteres especiales
2. **Foreign Keys:** Existen relaciones entre tablas - respetar orden de eliminación
3. **Timestamps:** Todas las tablas tienen `created_at` y `updated_at`
4. **Índices:** Optimizados para búsquedas frecuentes (clientes, órdenes, repuestos)
5. **Enum values:** Respeta los valores exactos definidos

---

## 🚨 Troubleshooting

### Error: "Unknown database 'sertecapp'"
```sql
CREATE DATABASE sertecapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Error: "Access denied for user"
```bash
# Verificar permisos
mysql -u root -p
GRANT ALL PRIVILEGES ON sertecapp.* TO 'tu_usuario'@'localhost';
FLUSH PRIVILEGES;
```

### Error: "Specified key was too long"
```sql
# Ya está configurado en el dump, pero si hay error:
SET GLOBAL innodb_large_prefix = ON;
SET GLOBAL innodb_file_format = Barracuda;
SET GLOBAL innodb_file_per_table = ON;
```

### Error: "Foreign key constraint fails"
```sql
# Deshabilitar temporalmente
SET FOREIGN_KEY_CHECKS = 0;
SOURCE sertecapp_complete.sql;
SET FOREIGN_KEY_CHECKS = 1;
```

---

## 📞 Soporte

Si tenés problemas importando la base:
1. Verificar versión de MySQL (8.0+)
2. Verificar charset utf8mb4 soportado
3. Verificar permisos de usuario
4. Revisar logs de MySQL: `/var/log/mysql/error.log`
