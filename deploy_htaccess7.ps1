#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Crear .htaccess (v2 POST)" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: savefile con POST y form-urlencoded
# Usando los parámetros exactos que funcionaron en test_upload2.ps1
# ============================================
Write-Host "[1] savefile con POST form-urlencoded..." -ForegroundColor Yellow

$htaccessContent = @"
# MkController v3.0 - LiteSpeed
Require all granted

<FilesMatch "\.php$">
    SetHandler application/x-httpd-ea-php74
</FilesMatch>

<FilesMatch "\.(env|json|lock|md|gitignore)$">
    Require all denied
</FilesMatch>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ frontend/index.html [L]
</IfModule>
"@

Add-Type -AssemblyName System.Web
$encodedContent = [System.Web.HttpUtility]::UrlEncode($htaccessContent, [System.Text.Encoding]::UTF8)

# Intentar 1: POST con JSON body (como en test_upload2.ps1 línea 64-74)
Write-Host "  Intento 1: POST con JSON body..." -ForegroundColor Gray
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    
    $body = @{
        path = "$remoteDir/.htaccess"
        content = $htaccessContent
        file = '.htaccess'
    } | ConvertTo-Json
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"error"') {
        Write-Host "  ❌ Falló" -ForegroundColor Red
    } else {
        Write-Host "  ✅ Éxito!" -ForegroundColor Green
        goto :verify
    }
} catch { Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red }

# Intentar 2: GET con todos los parámetros en URL
Write-Host "  Intento 2: GET con parámetros en URL..." -ForegroundColor Gray
try {
    $encodedPath = [System.Web.HttpUtility]::UrlEncode("$remoteDir/.htaccess", [System.Text.Encoding]::UTF8)
    $encodedFile = [System.Web.HttpUtility]::UrlEncode('.htaccess', [System.Text.Encoding]::UTF8)
    
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&dir=$remoteDir&path=$encodedPath&content=$encodedContent&file=$encodedFile"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"error"') {
        Write-Host "  ❌ Falló" -ForegroundColor Red
    } else {
        Write-Host "  ✅ Éxito!" -ForegroundColor Green
        goto :verify
    }
} catch { Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red }

# Intentar 3: POST con form-urlencoded (como en test_upload2.ps1 línea 77-88)
Write-Host "  Intento 3: POST con form-urlencoded..." -ForegroundColor Gray
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    
    $formBody = "path=$remoteDir/.htaccess&content=$encodedContent&file=.htaccess"
    
    $formHeaders = $headers.Clone()
    $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
    
    $r = Invoke-WebRequest -Uri $url -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"error"') {
        Write-Host "  ❌ Falló" -ForegroundColor Red
    } else {
        Write-Host "  ✅ Éxito!" -ForegroundColor Green
        goto :verify
    }
} catch { Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red }

# Intentar 4: Usar dir en lugar de path
Write-Host "  Intento 4: POST con dir + file..." -ForegroundColor Gray
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    
    $body = @{
        dir = $remoteDir
        content = $htaccessContent
        file = '.htaccess'
    } | ConvertTo-Json
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    if ($r.Content -match '"error"') {
        Write-Host "  ❌ Falló" -ForegroundColor Red
    } else {
        Write-Host "  ✅ Éxito!" -ForegroundColor Green
        goto :verify
    }
} catch { Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red }

# Intentar 5: Subir como htaccess.txt y luego RENAME (no copy)
Write-Host "  Intento 5: upload + rename..." -ForegroundColor Gray
try {
    # Eliminar htaccess.txt si existe
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/htaccess.txt"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    
    # Subir htaccess.txt
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    $filePath = "C:\xampp2\htdocs\mk\htaccess.txt"
    $boundary = [System.Guid]::NewGuid().ToString()
    $fileContent = [System.IO.File]::ReadAllBytes($filePath)
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="file-0"; filename="htaccess.txt"'
    $bodyLines += "Content-Type: text/plain"
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
    Write-Host "  Upload: OK" -ForegroundColor Gray
    
    # RENAME htaccess.txt -> .htaccess (usando op=rename con source-files y destination-files)
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=rename&source-files[]=htaccess.txt&destination-files[]=.htaccess&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Rename: $($r.Content)" -ForegroundColor Gray
    
    if ($r.Content -match '"result":1') {
        Write-Host "  ✅ Rename exitoso!" -ForegroundColor Green
    } else {
        Write-Host "  ❌ Rename falló" -ForegroundColor Red
    }
} catch { Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red }

# ============================================
# Verificar
# ============================================
Write-Host ""
Write-Host "[2] Verificando .htaccess..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    $found = $false
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.htaccess') {
            Write-Host "  ✅ .htaccess presente ($($item.humansize))" -ForegroundColor Green
            $found = $true
        }
    }
    if (-not $found) {
        Write-Host "  ❌ .htaccess NO encontrado" -ForegroundColor Red
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# Probar HTTP
# ============================================
Write-Host ""
Write-Host "[3] Probando acceso HTTP..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Sitio responde con código $($r.StatusCode)" -ForegroundColor Green
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Sitio responde con código: $statusCode" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Proceso completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
