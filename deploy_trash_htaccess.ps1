#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Eliminar .htaccess" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Eliminar .htaccess
# ============================================
Write-Host "[1] Eliminando .htaccess..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/.htaccess"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"result":1') {
        Write-Host "  ✅ .htaccess eliminado!" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ Puede que no exista" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 2: Verificar que el sitio ya no sirva passenger.js
# ============================================
Write-Host ""
Write-Host "[2] Verificando sitio..." -ForegroundColor Yellow
Start-Sleep -Seconds 5
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Código: $($r.StatusCode)" -ForegroundColor Green
    Write-Host "  Content (primeros 100 chars): $($r.Content.Substring(0, [Math]::Min(100, $r.Content.Length)))" -ForegroundColor Gray
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Código: $statusCode" -ForegroundColor Yellow
}

# ============================================
# PASO 3: Ejecutar fix_htaccess_final.php
# ============================================
Write-Host ""
Write-Host "[3] Ejecutando fix_htaccess_final.php..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/fix_htaccess_final.php" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response:" -ForegroundColor White
    Write-Host $r.Content -ForegroundColor Green
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 4: Probar acceso final
# ============================================
Write-Host ""
Write-Host "[4] Probando acceso final..." -ForegroundColor Yellow
Start-Sleep -Seconds 5
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Sitio responde con código $($r.StatusCode)" -ForegroundColor Green
    if ($r.Content.Length -gt 0) {
        Write-Host "  Content preview: $($r.Content.Substring(0, [Math]::Min(200, $r.Content.Length)))" -ForegroundColor Gray
    }
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Sitio responde con código: $statusCode" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Proceso completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
