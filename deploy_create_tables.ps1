# Script para subir y ejecutar create_tables_and_restart.php en el servidor
$cpanelUser = "nexusyl"
$cpanelPass = "n0A3`$oDTToa4%Z7"
$baseUrl = "https://server166.web-hosting.com:2083"
$domain = "nexusmk.nexussolutionsyl.com"

# Leer el contenido del archivo PHP y convertirlo a base64
$phpContent = Get-Content -Path "C:\xampp2\htdocs\mk\create_tables_and_restart.php" -Raw
$bytes = [System.Text.Encoding]::UTF8.GetBytes($phpContent)
$b64 = [Convert]::ToBase64String($bytes)

Write-Host "=== SUBIENDO create_tables_and_restart.php ===" -ForegroundColor Cyan

# 1. Subir el archivo usando write_file.php
$uploadUrl = "https://$domain/write_file.php"
$body = "path=/home/nexusyl/nexusmk.nexussolutionsyl.com/create_tables_and_restart.php&content_b64=$b64"

# Usar curl.exe (funciona con cPanel API)
$response = curl.exe -s -X POST -d $body $uploadUrl 2>&1
Write-Host "Upload response: $response" -ForegroundColor Yellow

# 2. Ejecutar el script
Write-Host "`n=== EJECUTANDO create_tables_and_restart.php ===" -ForegroundColor Cyan
$execUrl = "https://$domain/create_tables_and_restart.php"
$response = curl.exe -s --max-time 60 $execUrl 2>&1
Write-Host "`n=== RESULTADO ===" -ForegroundColor Green
Write-Host $response

# 3. Limpiar - eliminar el script temporal
Write-Host "`n=== LIMPIANDO ===" -ForegroundColor Cyan
$deleteUrl = "https://server166.web-hosting.com:2083/json-api/cpanel"
$deleteParams = "cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2&op=trash&sourcefiles=/home/nexusyl/nexusmk.nexussolutionsyl.com/create_tables_and_restart.php"
$auth = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes("${cpanelUser}:${cpanelPass}"))
$response = curl.exe -s -X POST -d $deleteParams -H "Authorization: Basic $auth" $deleteUrl 2>&1
Write-Host "Cleanup: $response" -ForegroundColor Gray
