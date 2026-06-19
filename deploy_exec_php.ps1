#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Ejecutar PHP via API" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Buscar API para ejecutar comandos
# ============================================
Write-Host "[1] Buscando API para ejecutar comandos..." -ForegroundColor Yellow

$apisToTest = @(
    @{url="https://server166.web-hosting.com:2083/execute/Execute/command"; method="POST"; body=@{command='php -v'} },
    @{url="https://server166.web-hosting.com:2083/execute/Command/execute"; method="POST"; body=@{command='php -v'} },
    @{url="https://server166.web-hosting.com:2083/execute/System/execute"; method="POST"; body=@{command='php -v'} },
    @{url="https://server166.web-hosting.com:2083/execute/Shell/exec"; method="POST"; body=@{command='php -v'} },
    @{url="https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Execute&cpanel_jsonapi_func=command&cpanel_jsonapi_apiversion=2"; method="GET"}
)

foreach ($api in $apisToTest) {
    try {
        if ($api.method -eq "POST") {
            $bodyJson = $api.body | ConvertTo-Json
            $r = Invoke-WebRequest -Uri $api.url -Headers $headers -Method POST -Body $bodyJson -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
        } else {
            $r = Invoke-WebRequest -Uri $api.url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        }
        Write-Host "  $($api.url): $($r.Content)" -ForegroundColor Gray
    } catch {
        Write-Host "  $($api.url): ERROR $($_.Exception.Message)" -ForegroundColor DarkGray
    }
}

# ============================================
# PASO 2: Intentar ejecutar PHP vía HTTP directo
# El sitio da 403, pero probemos con un path específico
# ============================================
Write-Host ""
Write-Host "[2] Probando acceso directo a scripts PHP..." -ForegroundColor Yellow

$phpScripts = @(
    "https://nexusmk.nexussolutionsyl.com/create_htaccess.php",
    "https://nexusmk.nexussolutionsyl.com/fix_all.php",
    "https://nexusmk.nexussolutionsyl.com/fix_app.php",
    "https://nexusmk.nexussolutionsyl.com/backend/app.js",
    "https://nexusmk.nexussolutionsyl.com/frontend/index.html"
)

foreach ($script in $phpScripts) {
    try {
        $r = Invoke-WebRequest -Uri $script -UseBasicParsing -TimeoutSec 10
        Write-Host "  $script -> $($r.StatusCode) ($($r.Content.Length) bytes)" -ForegroundColor Green
        if ($r.Content.Length -gt 0 -and $r.Content.Length -lt 500) {
            Write-Host "    Content: $($r.Content)" -ForegroundColor Gray
        }
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "  $script -> $statusCode" -ForegroundColor Yellow
    }
}

# ============================================
# PASO 3: Probar si podemos acceder a algún path
# ============================================
Write-Host ""
Write-Host "[3] Probando paths alternativos..." -ForegroundColor Yellow

$paths = @(
    "https://nexusmk.nexussolutionsyl.com/",
    "https://nexusmk.nexussolutionsyl.com/index.html",
    "https://nexusmk.nexussolutionsyl.com/frontend/",
    "https://nexusmk.nexussolutionsyl.com/frontend/index.html",
    "https://nexusmk.nexussolutionsyl.com/public/",
    "https://nexusmk.nexussolutionsyl.com/tmp/",
    "https://nexusmk.nexussolutionsyl.com/mk.zip"
)

foreach ($path in $paths) {
    try {
        $r = Invoke-WebRequest -Uri $path -UseBasicParsing -TimeoutSec 10
        Write-Host "  $path -> $($r.StatusCode)" -ForegroundColor Green
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        Write-Host "  $path -> $statusCode" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Diagnóstico completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
