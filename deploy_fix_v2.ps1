#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Fix .htaccess v2" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Subir fix_htaccess_v2.php
# ============================================
Write-Host "[1] Subiendo fix_htaccess_v2.php..." -ForegroundColor Yellow

# Primero eliminar si existe
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/fix_htaccess_v2.php"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
} catch {}

# Subir archivo
try {
    $localFile = "fix_htaccess_v2.php"
    $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
    $fileContent = [System.Text.Encoding]::Default.GetString($fileBytes)
    
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"fix_htaccess_v2.php`""
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += $fileContent
    $bodyLines += "--$boundary--"
    
    $bodyString = $bodyLines -join $lf
    
    $uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Upload response: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"result":1') {
        Write-Host "  ✅ fix_htaccess_v2.php subido!" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ Puede haber fallado" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 2: Ejecutar fix_htaccess_v2.php
# ============================================
Write-Host ""
Write-Host "[2] Ejecutando fix_htaccess_v2.php..." -ForegroundColor Yellow
Start-Sleep -Seconds 3
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/fix_htaccess_v2.php" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Response:" -ForegroundColor White
    Write-Host $r.Content -ForegroundColor Green
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 3: Probar acceso
# ============================================
Write-Host ""
Write-Host "[3] Probando acceso al sitio..." -ForegroundColor Yellow
Start-Sleep -Seconds 5
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Código: $($r.StatusCode)" -ForegroundColor Green
    if ($r.Content.Length -gt 0) {
        Write-Host "  Content preview (primeros 200 chars):" -ForegroundColor Gray
        Write-Host $r.Content.Substring(0, [Math]::Min(200, $r.Content.Length))
    }
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Código: $statusCode" -ForegroundColor Yellow
    if ($statusCode -eq 403) {
        Write-Host "  ⚠️ Aún 403 - LiteSpeed puede tener configuración adicional" -ForegroundColor Yellow
    }
}

# ============================================
# PASO 4: Probar acceso a archivos específicos
# ============================================
Write-Host ""
Write-Host "[4] Probando archivos específicos..." -ForegroundColor Yellow
$urls = @(
    "https://nexusmk.nexussolutionsyl.com/frontend/index.html",
    "https://nexusmk.nexussolutionsyl.com/backend/app.js",
    "https://nexusmk.nexussolutionsyl.com/fix_htaccess_v2.php"
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
Write-Host "  Proceso completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
