@echo off
REM deploy.bat — sincroniza local con Hostinger en un comando
REM Uso: deploy.bat "mensaje del commit"

SET MSG=%~1
IF "%MSG%"=="" SET MSG=fix: actualizacion de codigo

echo.
echo [1/4] Sincronizando archivos backend-laravel -^> raiz del repo ...
cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp"
robocopy backend-laravel\app app /E /XO /NFL /NDL /NJH /NJS > nul
robocopy backend-laravel\config config /E /XO /NFL /NDL /NJH /NJS > nul
robocopy backend-laravel\routes routes /E /XO /NFL /NDL /NJH /NJS > nul
robocopy backend-laravel\resources resources /E /XO /NFL /NDL /NJH /NJS > nul
echo     OK

echo.
echo [2/4] Commiteando en git...
git add app/ config/ routes/ resources/ backend-laravel/app/ backend-laravel/config/ backend-laravel/routes/ backend-laravel/resources/
git add CLAUDE.md DEPLOY_CI_GUIDE.md deploy.bat deploy-sertecapp.sh
git diff --cached --quiet && (echo     Sin cambios nuevos.) || (git commit -m "%MSG%" && echo     OK)

echo.
echo [3/4] Pusheando a GitHub...
git push origin development
echo     OK

echo.
echo [4/4] Deploy en Hostinger via SSH...
ssh -i "C:\Users\Hugo Pendziuch\.ssh\hostinger_sertecapp" -p 65002 u283281385@147.79.103.125 "cd /home/u283281385/domains/demo.pendziuch.com/public_html/backend-laravel && git pull origin development && php artisan view:clear && php artisan cache:clear"

echo.
echo ============================================
echo    Deploy completado. Verificar en:
echo    https://demo.pendziuch.com/sertecapp
echo ============================================
