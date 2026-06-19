#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Buscar Node.js Selector DB" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Subir find_nodejs_selector_db.php
Write-Host "[1] Subiendo find_nodejs_selector_db.php..." -ForegroundColor Yellow

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/find_nodejs_selector_db.php"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
} catch {}

try {
    $localFile = "find_nodejs_selector_db.php"
    $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
    $fileContent = [System.Text.Encoding]::Default.GetString($fileBytes)
    
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"find_nodejs_selector_db.php`""
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += $fileContent
    $bodyLines += "--$boundary--"
    
    $bodyString = $bodyLines -join $lf
    
    $uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Upload: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"result":1') {
        Write-Host "  Subido!" -ForegroundColor Green
    }
} catch {
    Write-Host "  Error upload: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# Ejecutar via URL directa
Write-Host "[2] Ejecutando find_nodejs_selector_db.php..." -ForegroundColor Yellow
try {
    $phpUrl = "https://nexusmk.nexussolutionsyl.com/find_nodejs_selector_db.php"
    $r = Invoke-WebRequest -Uri $phpUrl -Method Get -UseBasicParsing -TimeoutSec 60
    Write-Host "  Response:" -ForegroundColor Gray
    Write-Host $r.Content
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================"
