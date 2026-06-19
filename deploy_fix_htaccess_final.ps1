#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Fix .htaccess final" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Subir fix_htaccess_final.php
# ============================================
Write-Host "[1/3] Subiendo fix_htaccess_final.php..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    $filePath = "C:\xampp2\htdocs\mk\fix_htaccess_final.php"
    $boundary = [System.Guid]::NewGuid().ToString()
    $fileContent = [System.IO.File]::ReadAllBytes($filePath)
    $fileName = "fix_htaccess_final.php"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="file-0"; filename="' + $fileName + '"'
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += [System.Text.Encoding]::UTF8.GetString($fileContent)
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="dir"'
    $bodyLines += ""
    $bodyLines += $remoteDir
    $bodyLines += "--$boundary--"
    
    $bodyStr = $bodyLines -join "`r`n"
    $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyStr)
    $contentType = "multipart/form-data; boundary=$boundary"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $bodyBytes -ContentType $contentType -UseBasicParsing -TimeoutSec 30
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"status":1') {
        Write-Host "  ✅ Upload exitoso!" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ Puede que ya exista" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  ❌ Upload ERROR: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# ============================================
# PASO 2: Ejecutar el script
# ============================================
Write-Host ""
Write-Host "[2/3] Ejecutando fix_htaccess_final.php..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/fix_htaccess_final.php" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response:" -ForegroundColor White
    Write-Host $r.Content -ForegroundColor Green
} catch {
    Write-Host "  ❌ Error: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 3: Probar acceso HTTP
# ============================================
Write-Host ""
Write-Host "[3/3] Probando acceso HTTP..." -ForegroundColor Yellow
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
