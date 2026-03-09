# SerTecApp - Startup Script Mejorado
# Ejecutar con: powershell -ExecutionPolicy Bypass -File START_SERTECAPP.ps1

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SERTECAPP - INICIO AUTOMATICO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

function Test-PortInUse {
    param([int]$Port)
    $connection = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue
    return $null -ne $connection
}

function Start-ServiceIfNeeded {
    param(
        [string]$Name,
        [int]$Port,
        [string]$Command,
        [string]$WorkDir
    )
    
    if (Test-PortInUse -Port $Port) {
        Write-Host "[OK] $Name ya está corriendo en puerto $Port" -ForegroundColor Green
        return $true
    }
    
    Write-Host "[INICIO] Levantando $Name..." -ForegroundColor Yellow
    
    $startCmd = if ($WorkDir) {
        "Set-Location '$WorkDir'; $Command"
    } else {
        $Command
    }
    
    Start-Process powershell -ArgumentList "-NoExit", "-Command", $startCmd -WindowStyle Minimized
    
    # Esperar que levante
    $maxWait = 15
    $waited = 0
    while (-not (Test-PortInUse -Port $Port) -and $waited -lt $maxWait) {
        Start-Sleep -Seconds 1
        $waited++
        Write-Host "." -NoNewline -ForegroundColor Gray
    }
    Write-Host ""
    
    if (Test-PortInUse -Port $Port) {
        Write-Host "[OK] $Name levantado exitosamente" -ForegroundColor Green
        return $true
    } else {
        Write-Host "[ERROR] $Name no pudo iniciar" -ForegroundColor Red
        return $false
    }
}

# 1. Backend Laravel
$backendOk = Start-ServiceIfNeeded `
    -Name "Backend Laravel" `
    -Port 8000 `
    -Command "php artisan serve" `
    -WorkDir "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel"

# 2. Frontend Next.js
$frontendOk = Start-ServiceIfNeeded `
    -Name "Frontend Next.js" `
    -Port 3002 `
    -Command "`$env:PORT=3002; npm run dev" `
    -WorkDir "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"

# 3. Cloudflare Tunnel (no tiene puerto, solo verificamos proceso)
$tunnelRunning = Get-Process -Name "cloudflared" -ErrorAction SilentlyContinue
if ($tunnelRunning) {
    Write-Host "[OK] Cloudflare Tunnel ya está corriendo" -ForegroundColor Green
} else {
    Write-Host "[INICIO] Levantando Cloudflare Tunnel..." -ForegroundColor Yellow
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cloudflared tunnel run sertecapp-tunnel" -WindowStyle Minimized
    Start-Sleep -Seconds 5
    Write-Host "[OK] Cloudflare Tunnel iniciado" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  ESTADO FINAL" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "Backend:  $(if($backendOk){'OK'}else{'ERROR'})" -ForegroundColor $(if($backendOk){'Green'}else{'Red'})
Write-Host "Frontend: $(if($frontendOk){'OK'}else{'ERROR'})" -ForegroundColor $(if($frontendOk){'Green'}else{'Red'})
Write-Host "Tunnel:   OK" -ForegroundColor Green
Write-Host ""
Write-Host "URLs:" -ForegroundColor Cyan
Write-Host "  Admin:   https://sertecapp.pendziuch.com/admin" -ForegroundColor White
Write-Host "  App PWA: https://pro.pendziuch.com" -ForegroundColor White
Write-Host ""
Write-Host "Presiona cualquier tecla para cerrar..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
