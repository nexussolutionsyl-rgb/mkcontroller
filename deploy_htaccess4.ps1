#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Verificar y forzar .htaccess" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Listar archivos (incluyendo ocultos)
# ============================================
Write-Host "[1] Listando archivos en directorio..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    Write-Host "  Archivos en el directorio:" -ForegroundColor White
    foreach ($item in $result.cpanelresult.data) {
        $hidden = if ($item.file.StartsWith('.')) { ' (OCULTO)' } else { '' }
        Write-Host "    - $($item.file)$hidden ($($item.humansize))" -ForegroundColor Gray
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 2: Ver si passenger.js existe
# ============================================
Write-Host ""
Write-Host "[2] Verificando passenger.js..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq 'passenger.js') {
            Write-Host "  ✅ passenger.js presente ($($item.humansize))" -ForegroundColor Green
        }
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 3: Crear .htaccess directamente con savefile (intentando con GET)
# ============================================
Write-Host ""
Write-Host "[3] Intentando crear .htaccess con savefile (GET)..." -ForegroundColor Yellow

# Codificar el contenido del .htaccess
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

# URL encode the content manually
$encodedContent = [System.Uri]::EscapeDataString($htaccessContent)
$encodedFile = [System.Uri]::EscapeDataString('.htaccess')
$encodedDir = [System.Uri]::EscapeDataString($remoteDir)

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&dir=$encodedDir&file=$encodedFile&content=$encodedContent"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
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
# PASO 4: Verificar .htaccess otra vez
# ============================================
Write-Host ""
Write-Host "[4] Verificando .htaccess..." -ForegroundColor Yellow
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
# PASO 5: Probar HTTP
# ============================================
Write-Host ""
Write-Host "[5] Probando acceso HTTP..." -ForegroundColor Yellow
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
