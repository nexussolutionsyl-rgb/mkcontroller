#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Crear .htaccess (UAPI v3)" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Intentar con UAPI v3 Fileman/savefile
# ============================================
Write-Host "[1] Probando UAPI v3 Fileman/savefile..." -ForegroundColor Yellow

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

try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/savefile"
    $body = @{
        dir = $remoteDir
        file = '.htaccess'
        content = $htaccessContent
    } | ConvertTo-Json
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
    
    $result = $r.Content | ConvertFrom-Json
    if ($result.errors -and $result.errors.Count -gt 0) {
        Write-Host "  ❌ Error: $($result.errors[0])" -ForegroundColor Red
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
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 3: Probar HTTP
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
