#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Diagnóstico PassengerApps" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# 1. Listar aplicaciones Passenger
# ============================================
Write-Host "[1] Listando PassengerApps..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    $data = $r.Content | ConvertFrom-Json
    if ($data.data) {
        foreach ($app in $data.data) {
            Write-Host "  App: $($app.name)" -ForegroundColor White
            Write-Host "    Domain: $($app.domain)" -ForegroundColor Gray
            Write-Host "    Path: $($app.path)" -ForegroundColor Gray
            Write-Host "    Enabled: $($app.enabled)" -ForegroundColor Gray
            Write-Host "    Deployment mode: $($app.deployment_mode)" -ForegroundColor Gray
        }
    } else {
        Write-Host "  No apps found or error: $($r.Content)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 2. Verificar si hay PassengerEnabled en .htaccess
# ============================================
Write-Host ""
Write-Host "[2] Verificando .htaccess via PHP..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/fix_htaccess_v3.php" -UseBasicParsing -TimeoutSec 15
    # Just check if PHP executes
    if ($r.Content -match "COMPLETADO") {
        Write-Host "  ✅ PHP ejecuta correctamente" -ForegroundColor Green
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 3. Probar con cURL-style request a la raíz
# ============================================
Write-Host ""
Write-Host "[3] Headers de respuesta de la raíz..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)" -ForegroundColor Green
    foreach ($h in $r.Headers.Keys) {
        Write-Host "  $h : $($r.Headers[$h])" -ForegroundColor Gray
    }
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Status: $statusCode" -ForegroundColor Red
    try {
        $response = $_.Exception.Response
        foreach ($h in $response.Headers.Keys) {
            Write-Host "  $h : $($response.Headers[$h])" -ForegroundColor Gray
        }
    } catch {}
}

# ============================================
# 4. Verificar si el dominio tiene redirección
# ============================================
Write-Host ""
Write-Host "[4] Verificando configuración del dominio..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/DomainInfo/domains_data?format=hash"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    $data = $r.Content | ConvertFrom-Json
    if ($data.data.nexusmk) {
        Write-Host "  Dominio: nexusmk.nexussolutionsyl.com" -ForegroundColor White
        Write-Host "  DocumentRoot: $($data.data.nexusmk.documentroot)" -ForegroundColor Gray
        Write-Host "  Type: $($data.data.nexuswk.type)" -ForegroundColor Gray
    } else {
        Write-Host "  No se encontró información específica" -ForegroundColor Yellow
        Write-Host "  Response: $($r.Content.Substring(0, [Math]::Min(500, $r.Content.Length)))" -ForegroundColor Gray
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Diagnóstico completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
