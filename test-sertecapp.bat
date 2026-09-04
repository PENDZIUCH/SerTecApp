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
echo === FIN ===
