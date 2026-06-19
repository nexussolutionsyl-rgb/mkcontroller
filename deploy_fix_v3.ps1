#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Fix .htaccess v3" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Subir fix_htaccess_v3.php
# ============================================
Write-Host "[1] Subiendo fix_htaccess_v3.php..." -ForegroundColor Yellow

# Eliminar si existe
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/fix_htaccess_v3.php"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
} catch {}

# Subir archivo
try {
    $localFile = "fix_htaccess_v3.php"
    $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
    $fileContent = [System.Text.Encoding]::Default.GetString($fileBytes)
    
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"fix_htaccess_v3.php`""
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += $fileContent
    $bodyLines += "--$boundary--"
    
    $bodyString = $bodyLines -join $lf
    
    $uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Upload: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"result":1') {
        Write-Host "  ✅ Subido!" -ForegroundColor Green
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 2: Ejecutar fix_htaccess_v3.php
# ============================================
Write-Host ""
Write-Host "[2] Ejecutando fix_htaccess_v3.php..." -ForegroundColor Yellow
Start-Sleep -Seconds 3
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/fix_htaccess_v3.php" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Response:" -ForegroundColor White
    Write-Host $r.Content -ForegroundColor Green
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 3: Probar acceso
# ============================================
Write-Host ""
Write-Host "[3] Probando acceso..." -ForegroundColor Yellow
Start-Sleep -Seconds 5

$testUrls = @(
    @{Url="https://nexusmk.nexussolutionsyl.com/"; Desc="Raíz"},
    @{Url="https://nexusmk.nexussolutionsyl.com/frontend/index.html"; Desc="index.html"},
    @{Url="https://nexusmk.nexussolutionsyl.com/backend/app.js"; Desc="app.js"},
    @{Url="https://nexusmk.nexussolutionsyl.com/fix_htaccess_v3.php"; Desc="PHP script"}
)

foreach ($test in $testUrls) {
    try {
        $r = Invoke-WebRequest -Uri $test.Url -UseBasicParsing -TimeoutSec 10
        $preview = ""
        if ($r.Content.Length -gt 0) {
            $preview = $r.Content.Substring(0, [Math]::Min(100, $r.Content.Length))
        }
        Write-Host "  $($test.Desc): $($r.StatusCode) ($($r.Content.Length) bytes)" -ForegroundColor Green
        if ($preview) { Write-Host "    -> $preview" -ForegroundColor Gray }
    } catch {
        $code = $_.Exception.Response.StatusCode.value__
        Write-Host "  $($test.Desc): $code" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
