cd "C:\Users\Hugo Pendziuch\Documents\claude\SerTecApp\sertecapp-tecnicos"
npm run build
if ($LASTEXITCODE -eq 0) {
    Write-Host "Build exitoso!"
    
    # Matar procesos node en puerto 3002
    Get-Process | Where-Object {$_.ProcessName -eq "node"} | Where-Object {
        (Get-NetTCPConnection -OwningProcess $_.Id -ErrorAction SilentlyContinue | Where-Object {$_.LocalPort -eq 3002})
    } | Stop-Process -Force
    
    Start-Sleep -Seconds 2
    
    # Iniciar servidor
    $env:PORT=3002
    npm run start
}
