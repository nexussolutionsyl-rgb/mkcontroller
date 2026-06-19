#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Deshabilitar ModSecurity" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# 1. Deshabilitar ModSecurity para nexusmk
# ============================================
Write-Host "[1] Deshabilitando ModSecurity para nexusmk..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/ModSecurity/disable_domains"
    $body = @{
        domains = "nexusmk.nexussolutionsyl.com"
    }
    $json = $body | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $json -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"status":1') {
        Write-Host "  ✅ ModSecurity deshabilitado!" -ForegroundColor Green
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 2. Esperar y probar
# ============================================
Write-Host ""
Write-Host "[2] Esperando 10 segundos..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host "[3] Probando raíz..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Status: $($r.StatusCode)" -ForegroundColor Green
    if ($r.Content.Length -gt 0) {
        Write-Host "  Content: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))" -ForegroundColor Gray
    }
} catch {
    $code = $_.Exception.Response.StatusCode.value__
    Write-Host "  Status: $code" -ForegroundColor Red
    try {
        $response = $_.Exception.Response
        foreach ($h in $response.Headers.Keys) {
            Write-Host "  $h : $($response.Headers[$h])" -ForegroundColor Gray
        }
    } catch {}
}

# ============================================
# 4. Verificar estado de ModSecurity
# ============================================
Write-Host ""
Write-Host "[4] Verificando ModSecurity..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/ModSecurity/list_domains"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content -ForegroundColor Gray
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
