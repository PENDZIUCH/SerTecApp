@echo off
:: backup_d1.bat — Exporta la D1 de produccion a un archivo SQL con fecha
:: Correr manualmente o con Task Scheduler una vez por semana

set FECHA=%date:~6,4%-%date:~3,2%-%date:~0,2%
set OUTDIR=C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backups
set OUTFILE=%OUTDIR%\sertecapp_backup_%FECHA%.sql

if not exist "%OUTDIR%" mkdir "%OUTDIR%"

echo [%date% %time%] Iniciando backup D1...
pushd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-worker"

echo -- Backup SerTecApp D1 - %FECHA% > "%OUTFILE%"
echo -- ================================ >> "%OUTFILE%"

:: Exportar cada tabla
for %%T in (roles users user_roles customers equipment_brands equipment_models equipments parts work_orders wo_parts_used work_order_partes parte_repuestos work_order_logs) do (
    echo Exportando %%T...
    npx wrangler d1 execute sertecapp-db --remote --command="SELECT * FROM %%T" --json >> "%OUTFILE%_%%T.json" 2>nul
)

echo [%date% %time%] Backup completado: %OUTFILE%
echo Los datos estan en: %OUTDIR%
popd
pause
