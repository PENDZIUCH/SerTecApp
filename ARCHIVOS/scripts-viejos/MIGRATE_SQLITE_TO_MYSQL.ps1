# SerTecApp - SQLite to MySQL Migration Script
# Uso: powershell -ExecutionPolicy Bypass -File MIGRATE_SQLITE_TO_MYSQL.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SERTECAPP - SQLITE → MYSQL LOCAL" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar que MySQL esté disponible
Write-Host "[CHECK] Verificando MySQL..." -ForegroundColor Yellow
$mysqlPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$mysqldumpPath = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe"

if (-not (Test-Path $mysqlPath)) {
    Write-Host "[ERROR] MySQL no encontrado en Laragon. ¿Está instalado?" -ForegroundColor Red
    exit 1
}
Write-Host "[OK] MySQL encontrado" -ForegroundColor Green

# 2. Crear base de datos MySQL
Write-Host ""
Write-Host "[CREAR] Base de datos sertecapp en MySQL..." -ForegroundColor Yellow
$createDbSQL = @"
CREATE DATABASE IF NOT EXISTS sertecapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sertecapp;
"@

$createDbSQL | & $mysqlPath -u root 2>&1
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Base de datos creada" -ForegroundColor Green
} else {
    Write-Host "[ERROR] No se pudo crear la base de datos" -ForegroundColor Red
    exit 1
}

# 3. Migrar esquema usando Artisan
Write-Host ""
Write-Host "[MIGRAR] Ejecutando migraciones de Laravel..." -ForegroundColor Yellow
Set-Location "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"

# Asegurarse de que .env.mysql.local exista
if (-not (Test-Path ".env.mysql.local")) {
    Write-Host "[ERROR] No se encontró .env.mysql.local" -ForegroundColor Red
    exit 1
}

# Copiar .env.mysql.local a .env (backup del .env actual primero)
if (Test-Path ".env") {
    Copy-Item ".env" ".env.sqlite.backup" -Force
    Write-Host "[BACKUP] .env original guardado como .env.sqlite.backup" -ForegroundColor Gray
}

Copy-Item ".env.mysql.local" ".env" -Force
Write-Host "[CONFIG] .env actualizado a MySQL" -ForegroundColor Gray

# Ejecutar migraciones
Write-Host "[MIGRAR] Ejecutando php artisan migrate..." -ForegroundColor Yellow
php artisan migrate --force 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Migraciones completadas" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Las migraciones fallaron" -ForegroundColor Red
    Write-Host "[ROLLBACK] Restaurando .env.sqlite.backup..." -ForegroundColor Yellow
    Copy-Item ".env.sqlite.backup" ".env" -Force
    exit 1
}

# 4. Verificar tablas
Write-Host ""
Write-Host "[VERIFY] Verificando tablas creadas..." -ForegroundColor Yellow
$showTablesSQL = "USE sertecapp; SHOW TABLES;"
$tableCount = ($showTablesSQL | & $mysqlPath -u root -N | Measure-Object -Line).Lines

if ($tableCount -gt 0) {
    Write-Host "[OK] Se encontraron $tableCount tablas" -ForegroundColor Green
} else {
    Write-Host "[ERROR] No se crearon tablas" -ForegroundColor Red
    exit 1
}

# 5. Ejecutar seeders si existen
Write-Host ""
Write-Host "[SEED] Ejecutando seeders..." -ForegroundColor Yellow
php artisan db:seed --class=DatabaseSeeder --force 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Seeders completados" -ForegroundColor Green
} else {
    Write-Host "[WARN] Seeders no encontrados o fallaron (esto es normal si no hay seeders)" -ForegroundColor Yellow
}

# 6. Resumen final
Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  ✅ MIGRACION COMPLETADA" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Próximos pasos:" -ForegroundColor White
Write-Host "1. Testear Excel importer/exporter en admin" -ForegroundColor Gray
Write-Host "2. Si todo anda bien, cambiar .env y levantar servidor" -ForegroundColor Gray
Write-Host ""
Write-Host "Para volver a SQLite:" -ForegroundColor Yellow
Write-Host "  copy .env.sqlite.backup .env" -ForegroundColor Gray
Write-Host ""
Write-Host "Presiona cualquier tecla para cerrar..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
