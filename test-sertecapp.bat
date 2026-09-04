@echo off
echo === SERTECAPP HEALTH CHECK ===
echo.

echo [1] PWA sertecapp.pendziuch.com
curl -s -o nul -w "  HTTP %%{http_code}\n" https://sertecapp.pendziuch.com

echo [2] PWA backup sertecapp-tecnicos.pages.dev
curl -s -o nul -w "  HTTP %%{http_code}\n" https://sertecapp-tecnicos.pages.dev

echo [3] Filament admin login
curl -s -o nul -w "  HTTP %%{http_code}\n" https://demo.pendziuch.com/sertecapp/login

echo [4] API health
curl -s -w "  HTTP %%{http_code}\n" https://demo.pendziuch.com/api/health

echo [5] API v1 login (esperado 422 sin credenciales validas)
curl -s -H "Content-Type: application/json" -H "Accept: application/json" -w "  HTTP %%{http_code}\n" -d "{\"email\":\"test@test.com\",\"password\":\"x\"}" https://demo.pendziuch.com/api/v1/login

echo.
echo === SEGURIDAD - endpoints que deben rechazar acceso anonimo (esperado 401) ===
echo Cerrados en la auditoria del 2026-09-04 - si alguno de estos NO da 401, es una regresion critica.

echo [6] magic-link/generate sin token
curl -s -H "Accept: application/json" -o nul -w "  HTTP %%{http_code}\n" -X POST -H "Content-Type: application/json" -d "{}" https://demo.pendziuch.com/api/v1/magic-link/generate

echo [7] ordenes/tecnico/1 sin token
curl -s -H "Accept: application/json" -o nul -w "  HTTP %%{http_code}\n" https://demo.pendziuch.com/api/v1/ordenes/tecnico/1

echo [8] partes/1 sin token
curl -s -H "Accept: application/json" -o nul -w "  HTTP %%{http_code}\n" https://demo.pendziuch.com/api/v1/partes/1

echo [9] POST partes sin token
curl -s -H "Accept: application/json" -o nul -w "  HTTP %%{http_code}\n" -X POST -H "Content-Type: application/json" -d "{}" https://demo.pendziuch.com/api/v1/partes

echo.
echo === PWA - Service Worker apunta al dominio correcto ===
echo [10] sw.js debe referenciar demo.pendziuch.com (no sertecapp.pendziuch.com)
curl -s https://sertecapp.pendziuch.com/sw.js | findstr /C:"demo" >nul && echo   OK: contiene "demo" || echo   ATENCION: no se encontro "demo" en sw.js - revisar manualmente

echo.
echo === FIN ===
echo Para el detalle de que corrige cada chequeo de seguridad, ver CLAUDE.md
echo seccion "Sesion 2026-09-04 (continuacion) - auditoria de seguridad completa + fixes"
