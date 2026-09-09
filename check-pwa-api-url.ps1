# Chequea que el bundle JS de la PWA en vivo (sertecapp.pendziuch.com) apunte
# a la API de produccion, no al fallback de desarrollo local ('http://localhost:8787',
# ver lib/config.ts). Ese fallback vive baked-in en el JS estatico en build time
# (Next.js export) segun la variable NEXT_PUBLIC_API_URL que Cloudflare Pages haya
# tenido seteada en ESE build puntual - si la variable se pierde (paso el 2026-09,
# ver CLAUDE.md), el codigo no cambia pero el sitio en vivo si, y sin este chequeo
# nadie se entera hasta que un usuario real no puede entrar.
#
# Uso: powershell -NoProfile -File check-pwa-api-url.ps1

$ErrorActionPreference = 'Stop'
$pwaUrl = 'https://sertecapp.pendziuch.com'

try {
    $html = (Invoke-WebRequest -Uri $pwaUrl -UseBasicParsing).Content
} catch {
    Write-Host "  ATENCION: no se pudo cargar $pwaUrl ($($_.Exception.Message))"
    exit 1
}

$chunkMatch = [regex]::Match($html, '_next/static/chunks/app/page-[a-f0-9]+\.js')
if (-not $chunkMatch.Success) {
    Write-Host "  ATENCION: no se encontro el chunk principal en el HTML - revisar manualmente ($pwaUrl)"
    exit 1
}

$chunkUrl = "$pwaUrl/$($chunkMatch.Value)"
try {
    $js = (Invoke-WebRequest -Uri $chunkUrl -UseBasicParsing).Content
} catch {
    Write-Host "  ATENCION: no se pudo cargar el chunk $chunkUrl ($($_.Exception.Message))"
    exit 1
}

if ($js -match 'localhost:8787') {
    Write-Host "  ATENCION CRITICA: el bundle en vivo apunta a localhost:8787 - falta o se perdio NEXT_PUBLIC_API_URL en Cloudflare Pages (sertecapp-live > Configuracion > Variables y secretos). Login y todo fetch a la API van a fallar para usuarios reales."
    exit 2
} elseif ($js -match 'demo\.pendziuch\.com') {
    Write-Host "  OK: el bundle en vivo apunta a demo.pendziuch.com"
    exit 0
} else {
    Write-Host "  ATENCION: no se encontro ni 'localhost:8787' ni 'demo.pendziuch.com' en el bundle - revisar manualmente ($chunkUrl)"
    exit 1
}
