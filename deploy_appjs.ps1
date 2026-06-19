# Script para desplegar backend/app.js modificado via cPanel API
$cpanelUser = "nexusyl"
$cpanelPass = "n0A3$oDTToa4%Z7"
$baseUrl = "https://server166.web-hosting.com:2083"
$remoteDir = "/home/nexusyl/nexusmk.nexussolutionsyl.com/backend"
$localFile = "C:\xampp2\htdocs\mk\backend\app.js"

$auth = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("${cpanelUser}:${cpanelPass}"))
$headers = @{ Authorization = "Basic $auth" }

Write-Host "=== Desplegando backend/app.js ===" -ForegroundColor Cyan

# Leer el archivo local
$content = [System.IO.File]::ReadAllText($localFile)
$encoded = [System.Web.HttpUtility]::UrlEncode($content)

# Usar savefile API v2
$saveUrl = "${baseUrl}/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
$body = "dir=$([System.Uri]::EscapeDataString($remoteDir))&file=app.js&content=$encoded"

Write-Host "Subiendo app.js..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri $saveUrl -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 30
    Write-Host "RESPUESTA: $($r.Content)" -ForegroundColor Green
} catch {
    Write-Host "ERROR: $($_.Exception.Message)" -ForegroundColor Red
    # Intentar con GET
    Write-Host "`nIntentando con GET..." -ForegroundColor Yellow
    try {
        $getUrl = "${baseUrl}/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&dir=$([System.Uri]::EscapeDataString($remoteDir))&file=app.js&content=$encoded"
        $r = Invoke-WebRequest -Uri $getUrl -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 30
        Write-Host "RESPUESTA: $($r.Content)" -ForegroundColor Green
    } catch {
        Write-Host "ERROR GET: $($_.Exception.Message)" -ForegroundColor Red
    }
}
