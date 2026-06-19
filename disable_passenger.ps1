#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Desactivar PassengerApps" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# 1. Listar apps
# ============================================
Write-Host "[1] Listando apps..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "  $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 2. Desactivar app nexusmk
# ============================================
Write-Host ""
Write-Host "[2] Desactivando app nexusmk..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $body = @{
        name = "nexusmk"
        enabled = 0
    }
    $json = $body | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $json -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"status":1') {
        Write-Host "  ✅ App desactivada!" -ForegroundColor Green
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 3. Esperar y probar
# ============================================
Write-Host ""
Write-Host "[3] Esperando 10 segundos..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host "[4] Probando raíz..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Status: $($r.StatusCode)" -ForegroundColor Green
    Write-Host "  Content: $($r.Content.Substring(0, [Math]::Min(200, $r.Content.Length)))" -ForegroundColor Gray
} catch {
    $code = $_.Exception.Response.StatusCode.value__
    Write-Host "  Status: $code" -ForegroundColor Red
}

# ============================================
# 5. Si funciona, reactivar
# ============================================
Write-Host ""
Write-Host "[5] Reactivando app nexusmk..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $body = @{
        name = "nexusmk"
        enabled = 1
    }
    $json = $body | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $json -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"status":1') {
        Write-Host "  ✅ App reactivada!" -ForegroundColor Green
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
