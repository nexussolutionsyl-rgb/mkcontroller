#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Probar reglas del .htaccess" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Probar archivos que deberían estar DENEGADOS por .htaccess
$deniedUrls = @(
    "https://nexusmk.nexussolutionsyl.com/.env",
    "https://nexusmk.nexussolutionsyl.com/package.json",
    "https://nexusmk.nexussolutionsyl.com/passenger.js",
    "https://nexusmk.nexussolutionsyl.com/start.js"
)

Write-Host "Archivos que deberían estar DENEGADOS:" -ForegroundColor Yellow
foreach ($u in $deniedUrls) {
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 10
        Write-Host "  $u -> $($r.StatusCode) (DEBERÍA ESTAR DENEGADO!)" -ForegroundColor Red
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        if ($code -eq 403) {
            Write-Host "  $u -> $code ✅ (denegado correctamente)" -ForegroundColor Green
        } else {
            Write-Host "  $u -> $code" -ForegroundColor Yellow
        }
    }
}

# Probar archivos que deberían estar ACCESIBLES
Write-Host ""
Write-Host "Archivos que deberían estar ACCESIBLES:" -ForegroundColor Yellow
$allowedUrls = @(
    "https://nexusmk.nexussolutionsyl.com/frontend/index.html",
    "https://nexusmk.nexussolutionsyl.com/backend/app.js",
    "https://nexusmk.nexussolutionsyl.com/fix_htaccess_v3.php"
)
foreach ($u in $allowedUrls) {
    try {
        $r = Invoke-WebRequest -Uri $u -UseBasicParsing -TimeoutSec 10
        Write-Host "  $u -> $($r.StatusCode) ✅" -ForegroundColor Green
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        Write-Host "  $u -> $code ❌" -ForegroundColor Red
    }
}

# Probar con curl para ver headers completos
Write-Host ""
Write-Host "Headers completos de la raíz:" -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "Status: $($r.StatusCode)" -ForegroundColor Green
} catch {
    $code = $_.Exception.Response.StatusCode.value__
    Write-Host "Status: $code" -ForegroundColor Red
    try {
        $response = $_.Exception.Response
        $statusDescription = $response.StatusDescription
        Write-Host "Description: $statusDescription" -ForegroundColor Gray
        foreach ($h in $response.Headers.Keys) {
            Write-Host "  $h : $($response.Headers[$h])" -ForegroundColor Gray
        }
    } catch {}
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
