# SerTecApp - Startup Script
# Ejecutar este script cada vez que prendés la PC

Write-Host "==================================" -ForegroundColor Cyan
Write-Host "  LEVANTANDO SERTECAPP" -ForegroundColor Cyan
Write-Host "==================================" -ForegroundColor Cyan
Write-Host ""

# 1. Matar procesos anteriores
Write-Host "[1/3] Limpiando procesos anteriores..." -ForegroundColor Yellow
Get-Process | Where-Object {$_.ProcessName -like "*node*"} | Stop-Process -Force -ErrorAction SilentlyContinue
Get-Process | Where-Object {$_.ProcessName -like "*php*"} | Stop-Process -Force -ErrorAction SilentlyContinue
Get-Process | Where-Object {$_.ProcessName -like "*cloudflared*"} | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Seconds 2

# 2. Levantar Backend
Write-Host "[2/3] Levantando Backend Laravel (puerto 8000)..." -ForegroundColor Yellow
$backendPath = "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$backendPath'; php artisan serve" -WindowStyle Minimized

Start-Sleep -Seconds 3

# 3. Levantar Frontend
Write-Host "[3/3] Levantando Frontend Next.js (puerto 3002)..." -ForegroundColor Yellow
$frontendPath = "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$frontendPath'; `$env:PORT=3002; npm run start" -WindowStyle Minimized

Start-Sleep -Seconds 5

# 4. Levantar Tunnel
Write-Host "[4/4] Levantando Cloudflare Tunnel..." -ForegroundColor Yellow
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cloudflared tunnel run sertecapp" -WindowStyle Minimized

Start-Sleep -Seconds 5

Write-Host ""
Write-Host "==================================" -ForegroundColor Green
Write-Host "  ✅ SERTECAPP ONLINE" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Green
Write-Host ""
Write-Host "URLs:" -ForegroundColor Cyan
Write-Host "  Admin:    https://sertecapp.pendziuch.com" -ForegroundColor White
Write-Host "  App PWA:  https://pro.pendziuch.com" -ForegroundColor White
Write-Host ""
Write-Host "Procesos corriendo en ventanas minimizadas." -ForegroundColor Gray
Write-Host "Presiona cualquier tecla para cerrar este script..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
