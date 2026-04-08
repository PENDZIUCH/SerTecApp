# SerTecApp - Startup Script
# Uso manual:  powershell -ExecutionPolicy Bypass -File START_SERTECAPP.ps1
# Uso Claude:  powershell -ExecutionPolicy Bypass -NonInteractive -File START_SERTECAPP.ps1

param(
    [switch]$NoWait  # Pasar -NoWait para que no espere tecla al final (uso desde Claude)
)

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  SERTECAPP - INICIO AUTOMATICO" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

function Test-PortInUse {
    param([int]$Port)
    $connection = Get-NetTCPConnection -LocalPort $Port -ErrorAction SilentlyContinue
    return $null -ne $connection
}

# 1. Backend Laravel (admin panel)
if (Test-PortInUse -Port 8000) {
    Write-Host "[OK] Laravel ya corriendo en :8000" -ForegroundColor Green
} else {
    Write-Host "[INICIO] Levantando Laravel en :8000..." -ForegroundColor Yellow
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "Set-Location 'C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\backend-laravel'; php artisan serve --host=127.0.0.1 --port=8000" -WindowStyle Minimized
    $waited = 0
    while (-not (Test-PortInUse -Port 8000) -and $waited -lt 15) {
        Start-Sleep -Seconds 1
        $waited++
        Write-Host "." -NoNewline -ForegroundColor Gray
    }
    Write-Host ""
    if (Test-PortInUse -Port 8000) {
        Write-Host "[OK] Laravel levantado" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] Laravel no pudo iniciar" -ForegroundColor Red
    }
}

# 2. Cloudflare Tunnel
$tunnelRunning = Get-Process -Name "cloudflared" -ErrorAction SilentlyContinue
if ($tunnelRunning) {
    Write-Host "[OK] Cloudflare Tunnel ya corriendo" -ForegroundColor Green
} else {
    Write-Host "[INICIO] Levantando Cloudflare Tunnel..." -ForegroundColor Yellow
    Start-Process powershell -ArgumentList "-NoExit", "-Command", "cloudflared tunnel run sertecapp" -WindowStyle Minimized
    Start-Sleep -Seconds 5
    $tunnelNow = Get-Process -Name "cloudflared" -ErrorAction SilentlyContinue
    if ($tunnelNow) {
        Write-Host "[OK] Tunnel levantado" -ForegroundColor Green
    } else {
        Write-Host "[ERROR] Tunnel no pudo iniciar" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "  LISTO" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host "  Admin: https://sertecapp.pendziuch.com/admin" -ForegroundColor White
Write-Host "  Local: http://127.0.0.1:8000/admin" -ForegroundColor White
Write-Host ""

if (-not $NoWait) {
    Write-Host "Presiona cualquier tecla para cerrar..." -ForegroundColor Gray
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
}
