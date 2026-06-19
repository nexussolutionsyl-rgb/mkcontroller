#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Deploy Proxy PHP + Update .htaccess" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 1. Upload proxy.php
Write-Host "[1] Subiendo proxy.php..." -ForegroundColor Yellow

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/proxy.php"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
} catch {}

try {
    $localFile = "proxy.php"
    $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
    $fileContent = [System.Text.Encoding]::Default.GetString($fileBytes)
    
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"proxy.php`""
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

# 2. Update .htaccess via PHP script
Write-Host "[2] Actualizando .htaccess via PHP..." -ForegroundColor Yellow

# Create PHP script to update .htaccess
$phpScript = @'
<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$htaccess = $baseDir . '/.htaccess';

$content = '# MkController v3.0
# LiteSpeed / Apache

# === PERMITIR ACCESO ===
Require all granted
Satisfy Any
Order Allow,Deny
Allow from All

# === DIRECTORIO DE INICIO ===
DirectoryIndex index.html index.php

# === PHP HANDLER ===
<FilesMatch "\.php$">
    SetHandler application/x-httpd-ea-php74
</FilesMatch>

# === ARCHIVOS SENSIBLES ===
<FilesMatch "\.(env|json|lock|md|gitignore|ps1|txt|sqlite|db)$">
    Require all denied
</FilesMatch>

# === SEGURIDAD ===
Options -Indexes -MultiViews
ServerSignature Off

# === API PROXY ===
# Las rutas /api/* son manejadas por proxy.php
RewriteEngine On
RewriteBase /

# No aplicar rewrite a archivos existentes
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Rutas /api/* van a proxy.php
RewriteRule ^api/(.*)$ proxy.php [QSA,L]

# SPA: todo lo demas va a frontend/index.html
RewriteRule ^(.*)$ frontend/index.html [L]
';

$result = file_put_contents($htaccess, $content);
if ($result !== false) {
    echo "OK: .htaccess actualizado ($result bytes)\n";
} else {
    echo "ERROR: No se pudo escribir .htaccess\n";
}
echo "\nCOMPLETADO\n";
'@

# Upload the PHP script
$phpFile = "update_htaccess_proxy.php"
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/$phpFile"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
} catch {}

try {
    $fileBytes = [System.Text.Encoding]::ASCII.GetBytes($phpScript)
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"$phpFile`""
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += $phpScript
    $bodyLines += "--$boundary--"
    
    $bodyString = $bodyLines -join $lf
    
    $uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
    Write-Host "  Upload PHP: $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  Error upload PHP: $_" -ForegroundColor Yellow
}

# Execute the PHP script
Write-Host "  Ejecutando $phpFile..." -ForegroundColor Yellow
try {
    $phpUrl = "https://nexusmk.nexussolutionsyl.com/$phpFile"
    $r = Invoke-WebRequest -Uri $phpUrl -Method Get -UseBasicParsing -TimeoutSec 30
    Write-Host "  Response:" -ForegroundColor Gray
    Write-Host $r.Content
} catch {
    Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# 3. Test API
Write-Host "[3] Probando API /api/health..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -Method Get -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)" -ForegroundColor Green
    $contentPreview = $r.Content.Substring(0, [Math]::Min(500, $r.Content.Length))
    Write-Host "  Content: $contentPreview" -ForegroundColor Gray
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Status: $statusCode" -ForegroundColor Yellow
    try {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "  Body: $responseBody" -ForegroundColor Gray
    } catch {}
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================"
