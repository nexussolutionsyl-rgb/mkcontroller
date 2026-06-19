 o#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cpanelUser = "nexusyl"
$cpanelPass = "n0A3$oDTToa4%Z7"
$baseUrl = "https://server166.web-hosting.com:2083"
$remoteDir = "/home/nexusyl/nexusmk.nexussolutionsyl.com"
$localFile = "deploy_correcciones.php"

Write-Host "=== Subiendo deploy_correcciones.php al servidor ===" -ForegroundColor Cyan

# Autenticacion basica
$pair = "$($cpanelUser):$($cpanelPass)"
$encodedCreds = [System.Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{
    "Authorization" = "Basic $encodedCreds"
}

# PASO 1: Subir el archivo PHP via Fileman.uploadfiles
Write-Host "`n[1/2] Subiendo $localFile..." -ForegroundColor Yellow
$fileContent = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
$fileName = "deploy_correcciones.php"

$uploadUrl = "${baseUrl}/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"

$boundary = [System.Guid]::NewGuid().ToString()
$bodyLines = @()

# Parametros
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"dir`""
$bodyLines += ""
$bodyLines += $remoteDir

$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"$fileName`""
$bodyLines += "Content-Type: application/x-php"
$bodyLines += ""

$bodyHeader = ($bodyLines -join "`r`n") + "`r`n"
$bodyFooter = "`r`n--$boundary--`r`n"

$bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyHeader)
$footerBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyFooter)

$fullBody = New-Object System.Byte[] ($bodyBytes.Length + $fileContent.Length + $footerBytes.Length)
$bodyBytes.CopyTo($fullBody, 0)
$fileContent.CopyTo($fullBody, $bodyBytes.Length)
$footerBytes.CopyTo($fullBody, ($bodyBytes.Length + $fileContent.Length))

try {
    $response = Invoke-WebRequest -Uri $uploadUrl -Method Post -Headers $headers -Body $fullBody -ContentType "multipart/form-data; boundary=$boundary" -TimeoutSec 60 -UseBasicParsing
    $result = $response.Content | ConvertFrom-Json
    if ($result.cpanelresult.error) {
        Write-Host "ERROR: $($result.cpanelresult.error)" -ForegroundColor Red
        exit 1
    } else {
        Write-Host "Archivo subido exitosamente!" -ForegroundColor Green
        Write-Host "Response: $($response.Content)" -ForegroundColor Gray
    }
} catch {
    Write-Host "Error subiendo archivo: $_" -ForegroundColor Red
    
    # Intentar con savefile como fallback
    Write-Host "`nIntentando con Fileman::savefile..." -ForegroundColor Yellow
    $phpContent = [System.IO.File]::ReadAllText((Resolve-Path $localFile))
    $encoded = [System.Uri]::EscapeDataString($phpContent)
    
    $saveUrl = "${baseUrl}/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    $body = "dir=$([System.Uri]::EscapeDataString($remoteDir))&file=$fileName&content=$encoded"
    
    try {
        $saveResult = Invoke-WebRequest -Uri $saveUrl -Method POST -Headers $headers -Body $body -ContentType "application/x-www-form-urlencoded" -UseBasicParsing -TimeoutSec 60
        Write-Host "Archivo subido via savefile!" -ForegroundColor Green
        Write-Host "Response: $($saveResult.Content)" -ForegroundColor Gray
    } catch {
        Write-Host "Error en savefile: $_" -ForegroundColor Red
        exit 1
    }
}

# PASO 2: Ejecutar el script PHP via HTTP
Write-Host "`n[2/2] Ejecutando deploy_correcciones.php..." -ForegroundColor Yellow
$execUrl = "https://nexusmk.nexussolutionsyl.com/deploy_correcciones.php"

try {
    $execResult = Invoke-WebRequest -Uri $execUrl -UseBasicParsing -TimeoutSec 60
    Write-Host "Script ejecutado!" -ForegroundColor Green
    Write-Host "`n=== RESULTADO ===" -ForegroundColor Cyan
    Write-Host $execResult.Content -ForegroundColor White
} catch {
    Write-Host "Error ejecutando script: $_" -ForegroundColor Red
    Write-Host "(Esto es normal si el .htaccess no permite PHP aún)" -ForegroundColor Yellow
}

Write-Host "`n=== Proceso completado ===" -ForegroundColor Cyan
