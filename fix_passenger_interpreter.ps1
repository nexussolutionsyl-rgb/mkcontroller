#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$baseUrl = "https://server166.web-hosting.com:2083"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Fix Passenger Interpreter to Node.js" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 1. Intentar editar la app para cambiar el interprete
Write-Host "[1] Editando app nexusmk - cambiando interprete a Node.js..." -ForegroundColor Yellow

# Primero, ver que parametros acepta edit_application
Write-Host "  Probando diferentes combinaciones..." -ForegroundColor Gray

# Opcion 1: Con nodejs parameter
try {
    $url = "$baseUrl/execute/PassengerApps/edit_application"
    $bodyString = "name=nexusmk&nodejs=/opt/alt/alt-nodejs20/root/usr/bin/node"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $bodyString -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Edit 1 (nodejs param): $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  Error 1: $_" -ForegroundColor Yellow
}

# Opcion 2: Con interpreter parameter
try {
    $url = "$baseUrl/execute/PassengerApps/edit_application"
    $bodyString = "name=nexusmk&interpreter=node"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $bodyString -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Edit 2 (interpreter): $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  Error 2: $_" -ForegroundColor Yellow
}

# Opcion 3: Quitar ruby y poner nodejs
try {
    $url = "$baseUrl/execute/PassengerApps/edit_application"
    $bodyString = "name=nexusmk&ruby=&nodejs=/opt/alt/alt-nodejs20/root/usr/bin/node"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $bodyString -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Edit 3 (ruby vacio + nodejs): $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  Error 3: $_" -ForegroundColor Yellow
}

Write-Host ""

# 2. Ver estado actual
Write-Host "[2] Estado actual de la app..." -ForegroundColor Yellow
try {
    $url = "$baseUrl/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    $data = $r.Content | ConvertFrom-Json
    if ($data.data) {
        foreach ($app in $data.data) {
            Write-Host "  App: $($app.name)" -ForegroundColor Cyan
            Write-Host "    ruby: $($app.ruby)" -ForegroundColor Gray
            Write-Host "    python: $($app.python)" -ForegroundColor Gray
            Write-Host "    nodejs: $($app.nodejs)" -ForegroundColor Gray
            Write-Host "    enabled: $($app.enabled)" -ForegroundColor Gray
        }
    }
} catch {
    Write-Host "  Error: $_" -ForegroundColor Red
}

Write-Host ""

# 3. Probar API nuevamente
Write-Host "[3] Probando API /api/health..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -Method Get -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)" -ForegroundColor Green
    $contentPreview = $r.Content.Substring(0, [Math]::Min(200, $r.Content.Length))
    Write-Host "  Content preview: $contentPreview" -ForegroundColor Gray
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Status: $statusCode" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================"
