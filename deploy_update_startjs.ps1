$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  Actualizando start.js en el servidor"
Write-Host "============================================"
Write-Host ""

# 1. Subir start.js como startjs.txt (Fileman no soporta .js directamente?)
Write-Host "[1] Subiendo start.js..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    
    # Leer el archivo local
    $localFile = "C:\xampp2\htdocs\mk\start.js"
    $fileContent = [System.IO.File]::ReadAllBytes($localFile)
    
    $boundary = [Guid]::NewGuid().ToString()
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`"start.js`""
    $bodyLines += "Content-Type: application/octet-stream"
    $bodyLines += ""
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"dir`""
    $bodyLines += ""
    $bodyLines += "$remoteDir"
    $bodyLines += "--$boundary--"
    
    $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyLines -join "`r`n")
    $fullBody = New-Object byte[] ($bodyBytes.Length + $fileContent.Length + 4)
    [Array]::Copy($bodyBytes, 0, $fullBody, 0, $bodyBytes.Length)
    [Array]::Copy($fileContent, 0, $fullBody, $bodyBytes.Length, $fileContent.Length)
    $closing = [System.Text.Encoding]::UTF8.GetBytes("`r`n--$boundary--`r`n")
    [Array]::Copy($closing, 0, $fullBody, $bodyBytes.Length + $fileContent.Length, $closing.Length)
    
    $contentType = "multipart/form-data; boundary=$boundary"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $fullBody -ContentType $contentType -UseBasicParsing -TimeoutSec 30
    Write-Host "  Respuesta: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 2. Verificar que start.js se actualizó
Write-Host ""
Write-Host "[2] Verificando contenido de start.js..."
try {
    # Usar PHP para leer el archivo
    $phpScript = @'
<?php
echo "=== CONTENIDO DE start.js ===\n";
echo file_get_contents("/home/nexusyl/nexusmk.nexussolutionsyl.com/start.js");
echo "\n=== FIN ===\n";
?>
'@
    
    # Subir script PHP
    $phpBytes = [System.Text.Encoding]::UTF8.GetBytes($phpScript)
    $boundary2 = [Guid]::NewGuid().ToString()
    $bodyLines2 = @()
    $bodyLines2 += "--$boundary2"
    $bodyLines2 += "Content-Disposition: form-data; name=`"file`"; filename=`"read_startjs.php`""
    $bodyLines2 += "Content-Type: application/octet-stream"
    $bodyLines2 += ""
    $bodyLines2 += "--$boundary2"
    $bodyLines2 += "Content-Disposition: form-data; name=`"dir`""
    $bodyLines2 += ""
    $bodyLines2 += "$remoteDir"
    $bodyLines2 += "--$boundary2--"
    
    $bodyBytes2 = [System.Text.Encoding]::UTF8.GetBytes($bodyLines2 -join "`r`n")
    $fullBody2 = New-Object byte[] ($bodyBytes2.Length + $phpBytes.Length + 4)
    [Array]::Copy($bodyBytes2, 0, $fullBody2, 0, $bodyBytes2.Length)
    [Array]::Copy($phpBytes, 0, $fullBody2, $bodyBytes2.Length, $phpBytes.Length)
    $closing2 = [System.Text.Encoding]::UTF8.GetBytes("`r`n--$boundary2--`r`n")
    [Array]::Copy($closing2, 0, $fullBody2, $bodyBytes2.Length + $phpBytes.Length, $closing2.Length)
    
    $contentType2 = "multipart/form-data; boundary=$boundary2"
    
    $r2 = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $fullBody2 -ContentType $contentType2 -UseBasicParsing -TimeoutSec 30
    Write-Host "  Script PHP subido: $($r2.Content)"
    
    # Ejecutar el script PHP
    Start-Sleep -Seconds 2
    $phpUrl = "https://nexusmk.nexussolutionsyl.com/read_startjs.php"
    $r3 = Invoke-WebRequest -Uri $phpUrl -UseBasicParsing -TimeoutSec 15
    Write-Host "  $($r3.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 3. Limpiar script temporal
Write-Host ""
Write-Host "[3] Limpiando archivos temporales..."
try {
    $url2 = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=/home/nexusyl/nexusmk.nexussolutionsyl.com/read_startjs.php&dirs="
    $r4 = Invoke-WebRequest -Uri $url2 -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 10
    Write-Host "  Limpieza: $($r4.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
Write-Host "  Proceso completado"
Write-Host "============================================"
