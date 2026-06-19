#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Eliminar PassengerApps y probar" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# 1. Unregister app nexusmk
# ============================================
Write-Host "[1] Eliminando app nexusmk de PassengerApps..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/unregister_application"
    $body = @{
        name = "nexusmk"
    }
    $json = $body | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $json -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"status":1') {
        Write-Host "  ✅ App eliminada!" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ Puede haber fallado" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# 2. Esperar y probar
# ============================================
Write-Host ""
Write-Host "[2] Esperando 15 segundos..." -ForegroundColor Yellow
Start-Sleep -Seconds 15

Write-Host "[3] Probando raíz..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Status: $($r.StatusCode)" -ForegroundColor Green
    Write-Host "  Content (primeros 300 chars):" -ForegroundColor Gray
    Write-Host $r.Content.Substring(0, [Math]::Min(300, $r.Content.Length))
} catch {
    $code = $_.Exception.Response.StatusCode.value__
    Write-Host "  Status: $code" -ForegroundColor Red
    if ($code -eq 403) {
        Write-Host "  ⚠️ Aún 403 - LiteSpeed puede tener caché" -ForegroundColor Yellow
    }
}

# ============================================
# 4. Probar archivos específicos
# ============================================
Write-Host ""
Write-Host "[4] Probando archivos..." -ForegroundColor Yellow
$urls = @(
    "https://nexusmk.nexussolutionsyl.com/",
    "https://nexusmk.nexussolutionsyl.com/frontend/index.html",
    "https://nexusmk.nexussolutionsyl.com/backend/app.js",
    "https://nexusmk.nexussolutionsyl.com/fix_htaccess_v3.php"
)
foreach ($u in $urls) {
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 10
        Write-Host "  $u -> $($r.StatusCode) ($($r.Content.Length) bytes)" -ForegroundColor Green
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        Write-Host "  $u -> $code" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
