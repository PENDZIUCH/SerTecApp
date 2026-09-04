@echo off
cd /d "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
echo [1/3] Limpiando...
rmdir /s /q .next 2>nul
echo [2/3] Buildando...
set NEXT_EXPORT=1
npx next build --webpack
if errorlevel 1 ( echo ERROR en build & pause & exit /b 1 )
echo [3/3] Deployando...
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
echo DEPLOY EXITOSO - https://sertecapp-tecnicos.pages.dev
pause
