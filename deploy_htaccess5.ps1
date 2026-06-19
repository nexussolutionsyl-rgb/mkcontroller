#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Crear .htaccess via API v2" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Usar savefile con POST (x-www-form-urlencoded)
# ============================================
Write-Host "[1] Creando .htaccess con savefile (POST form)..." -ForegroundColor Yellow

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

# URL encode
Add-Type -AssemblyName System.Web
$encodedContent = [System.Web.HttpUtility]::UrlEncode($htaccessContent)
$encodedFile = [System.Web.HttpUtility]::UrlEncode('.htaccess')
$encodedDir = [System.Web.HttpUtility]::UrlEncode($remoteDir)

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel"
    $body = "cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&dir=$encodedDir&file=$encodedFile&content=$encodedContent"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    
    if ($r.Content -match '"error"') {
        Write-Host "  ❌ savefile falló" -ForegroundColor Red
    } else {
        Write-Host "  ✅ savefile exitoso!" -ForegroundColor Green
    }
} catch {
    Write-Host "  ❌ savefile ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 2: Verificar
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
        Write-Host "  Intentando método alternativo..." -ForegroundColor Yellow
        
        # Método alternativo: subir htaccess.txt y copiar a .htaccess
        Write-Host ""
        Write-Host "[3] Método alternativo: upload + copy..." -ForegroundColor Yellow
        
        # Primero eliminar htaccess.txt si existe
        try {
            $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
            $body = "op=trash&sourcefiles=$remoteDir/htaccess.txt"
            $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
        } catch {}
        
        # Subir htaccess.txt
        try {
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
            Write-Host "  Upload htaccess.txt: $($r.Content)" -ForegroundColor Gray
        } catch {
            Write-Host "  Upload ERROR: $($_.Exception.Message)" -ForegroundColor Red
        }
        
        # Copiar htaccess.txt a .htaccess (usando copy en lugar de rename)
        try {
            $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
            $body = "op=copy&source-files[]=htaccess.txt&destination-files[]=.htaccess&dir=$remoteDir"
            $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
            Write-Host "  Copy: $($r.Content)" -ForegroundColor Gray
        } catch {
            Write-Host "  Copy ERROR: $($_.Exception.Message)" -ForegroundColor Red
        }
        
        # Verificar otra vez
        Start-Sleep -Seconds 2
        try {
            $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
            $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
            $result = $r.Content | ConvertFrom-Json
            foreach ($item in $result.cpanelresult.data) {
                if ($item.file -eq '.htaccess') {
                    Write-Host "  ✅ .htaccess presente después de copy ($($item.humansize))" -ForegroundColor Green
                    $found = $true
                }
            }
            if (-not $found) {
                Write-Host "  ❌ .htaccess aún no encontrado" -ForegroundColor Red
            }
        } catch {}
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 4: Probar HTTP
# ============================================
Write-Host ""
Write-Host "[4] Probando acceso HTTP..." -ForegroundColor Yellow
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
