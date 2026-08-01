@echo off
REM deploy.bat — commit + push a development
REM Uso: deploy.bat "mensaje del commit"

SET MSG=%~1
IF "%MSG%"=="" SET MSG=fix: actualizacion de codigo

echo.
echo [1/3] Commiteando en git...
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"
git add backend-laravel/app/ backend-laravel/config/ backend-laravel/routes/ backend-laravel/resources/ backend-laravel/database/migrations/ CLAUDE.md DEPLOY_CI_GUIDE.md deploy.bat deploy-sertecapp.sh sertecapp-tecnicos/
git diff --cached --quiet && (echo     Sin cambios nuevos.) || (git commit -m "%MSG%" && echo     OK)

echo.
echo [2/3] Pusheando a GitHub...
git push origin development
echo     OK

echo.
echo [3/3] Deploy en Hostinger via webhook automatico...
echo     (el webhook dispara deploy-sertecapp.sh automaticamente)

echo.
echo ============================================
echo    Push completado. Verificar en:
echo    https://demo.pendziuch.com/sertecapp
echo ============================================
