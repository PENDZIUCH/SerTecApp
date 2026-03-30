@echo off
echo.
echo ==============================
echo   DEPLOY sertecapp-tecnicos
echo ==============================
echo.

cd /d "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"

echo [1/3] Limpiando build anterior...
rmdir /s /q .next 2>nul
echo OK

echo.
echo [2/3] Generando build estatico...
npx next build --webpack
if errorlevel 1 (
  echo.
  echo ERROR: El build fallo. Revisar errores arriba.
  pause
  exit /b 1
)

echo.
echo [3/3] Deployando a Cloudflare Pages...
npx wrangler pages deploy out --project-name sertecapp-tecnicos --branch main --commit-dirty=true
if errorlevel 1 (
  echo.
  echo ERROR: Deploy fallo. Puede que necesites hacer "npx wrangler login" primero.
  pause
  exit /b 1
)

echo.
echo ==============================
echo   DEPLOY EXITOSO
echo   https://sertecapp-tecnicos.pages.dev
echo ==============================
echo.
pause
