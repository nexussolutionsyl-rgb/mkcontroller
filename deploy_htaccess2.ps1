#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  MkController - Restaurar .htaccess" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# ============================================
# PASO 1: Subir htaccess.txt
# ============================================
Write-Host "[1/4] Subiendo htaccess.txt..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    $filePath = "C:\xampp2\htdocs\mk\htaccess.txt"
    $boundary = [System.Guid]::NewGuid().ToString()
    $fileContent = [System.IO.File]::ReadAllBytes($filePath)
    $fileName = "htaccess.txt"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="file-0"; filename="' + $fileName + '"'
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
    Write-Host "  ✅ Upload exitoso!" -ForegroundColor Green
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  ❌ Upload ERROR: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# ============================================
# PASO 2: Renombrar a .htaccess
# ============================================
Write-Host ""
Write-Host "[2/4] Renombrando a .htaccess..." -ForegroundColor Yellow
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=rename&source-files[]=htaccess.txt&destination-files[]=.htaccess&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Rename exitoso!" -ForegroundColor Green
    Write-Host "  Response: $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  ❌ Rename ERROR: $($_.Exception.Message)" -ForegroundColor Red
}

# ============================================
# PASO 3: Verificar .htaccess
# ============================================
Write-Host ""
Write-Host "[3/4] Verificando .htaccess..." -ForegroundColor Yellow
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
# PASO 4: Probar acceso HTTP
# ============================================
Write-Host ""
Write-Host "[4/4] Probando acceso HTTP..." -ForegroundColor Yellow
Start-Sleep -Seconds 5
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ Sitio responde con código $($r.StatusCode)" -ForegroundColor Green
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Sitio responde con código: $statusCode" -ForegroundColor Yellow
    if ($statusCode -eq 200) {
        Write-Host "  ✅ Sitio accesible!" -ForegroundColor Green
    } else {
        Write-Host "  ⚠️ Código: $statusCode" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Proceso completado" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
