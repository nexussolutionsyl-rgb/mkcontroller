# Script para subir setup_nexusmk_db.php al servidor
# Luego hay que ejecutarlo via web

$cpanelUser = "nexusyl"
$cpanelPass = "n0A3$oDTToa4%Z7"
$baseUrl = "https://server166.web-hosting.com:2083"
$subdomain = "nexusmk.nexussolutionsyl.com"
$remoteDir = "/home/nexusyl/nexusmk.nexussolutionsyl.com"

# Autenticacion basica
$pair = "$($cpanelUser):$($cpanelPass)"
$encodedCreds = [System.Convert]::ToBase64String([System.Text.Encoding]::ASCII.GetBytes($pair))
$headers = @{
    "Authorization" = "Basic $encodedCreds"
}

Write-Host "=== Subiendo setup_nexusmk_db.php al servidor ===" -ForegroundColor Cyan

# PASO 1: Subir el archivo PHP via Fileman.uploadfiles
$localFile = "setup_nexusmk_db.php"
$fileContent = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
$fileName = "setup_nexusmk_db.php"

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
# El contenido binario se agregara despues

$bodyHeader = ($bodyLines -join "`r`n") + "`r`n"
$bodyFooter = "`r`n--$boundary--`r`n"

$bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyHeader)
$footerBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyFooter)

$fullBody = New-Object System.Byte[] ($bodyBytes.Length + $fileContent.Length + $footerBytes.Length)
$bodyBytes.CopyTo($fullBody, 0)
$fileContent.CopyTo($fullBody, $bodyBytes.Length)
$footerBytes.CopyTo($fullBody, ($bodyBytes.Length + $fileContent.Length))

try {
    $response = Invoke-WebRequest -Uri $uploadUrl -Method Post -Headers $headers -Body $fullBody -ContentType "multipart/form-data; boundary=$boundary" -TimeoutSec 60
    $result = $response.Content | ConvertFrom-Json
    if ($result.cpanelresult.error) {
        Write-Host "ERROR: $($result.cpanelresult.error)" -ForegroundColor Red
    } else {
        Write-Host "Archivo subido exitosamente!" -ForegroundColor Green
    }
} catch {
    Write-Host "Error en upload: $_" -ForegroundColor Red
    
    # Metodo alternativo: PHP script que escribe el archivo
    Write-Host "`nIntentando metodo alternativo..." -ForegroundColor Yellow
    
    $phpContent = @"
<?php
`$content = base64_decode('BASE64_CONTENT');
file_put_contents('/home/nexusyl/nexusmk.nexussolutionsyl.com/setup_nexusmk_db.php', `$content);
echo "OK: " . filesize('/home/nexusyl/nexusmk.nexussolutionsyl.com/setup_nexusmk_db.php') . " bytes";
"@
    
    $localContent = [System.IO.File]::ReadAllText((Resolve-Path $localFile))
    $b64Content = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($localContent))
    $phpContent = $phpContent.Replace('BASE64_CONTENT', $b64Content)
    
    $tempPhpFile = "write_setup_db.php"
    [System.IO.File]::WriteAllText((Resolve-Path $tempPhpFile), $phpContent, [System.Text.Encoding]::UTF8)
    
    # Subir el script PHP auxiliar
    $tempContent = [System.IO.File]::ReadAllBytes((Resolve-Path $tempPhpFile))
    $bodyLines2 = @()
    $bodyLines2 += "--$boundary"
    $bodyLines2 += "Content-Disposition: form-data; name=`"dir`""
    $bodyLines2 += ""
    $bodyLines2 += $remoteDir
    $bodyLines2 += "--$boundary"
    $bodyLines2 += "Content-Disposition: form-data; name=`"file-0`"; filename=`"write_setup_db.php`""
    $bodyLines2 += "Content-Type: application/x-php"
    $bodyLines2 += ""
    
    $bodyHeader2 = ($bodyLines2 -join "`r`n") + "`r`n"
    $bodyFooter2 = "`r`n--$boundary--`r`n"
    $bodyBytes2 = [System.Text.Encoding]::UTF8.GetBytes($bodyHeader2)
    $footerBytes2 = [System.Text.Encoding]::UTF8.GetBytes($bodyFooter2)
    
    $fullBody2 = New-Object System.Byte[] ($bodyBytes2.Length + $tempContent.Length + $footerBytes2.Length)
    $bodyBytes2.CopyTo($fullBody2, 0)
    $tempContent.CopyTo($fullBody2, $bodyBytes2.Length)
    $footerBytes2.CopyTo($fullBody2, ($bodyBytes2.Length + $tempContent.Length))
    
    try {
        $response2 = Invoke-WebRequest -Uri $uploadUrl -Method Post -Headers $headers -Body $fullBody2 -ContentType "multipart/form-data; boundary=$boundary" -TimeoutSec 60
        $result2 = $response2.Content | ConvertFrom-Json
        if ($result2.cpanelresult.error) {
            Write-Host "ERROR metodo alternativo: $($result2.cpanelresult.error)" -ForegroundColor Red
        } else {
            Write-Host "Script auxiliar subido!" -ForegroundColor Green
            
            # Ejecutar el script auxiliar via web
            $execUrl = "https://$subdomain/write_setup_db.php"
            try {
                $execResponse = Invoke-WebRequest -Uri $execUrl -TimeoutSec 30
                Write-Host "Resultado: $($execResponse.Content)" -ForegroundColor Green
            } catch {
                Write-Host "Error ejecutando script: $_" -ForegroundColor Red
            }
        }
    } catch {
        Write-Host "Error en metodo alternativo: $_" -ForegroundColor Red
    }
}

Write-Host "`n=== INSTRUCCIONES ===" -ForegroundColor Cyan
Write-Host "1. Ve a cPanel: https://server166.web-hosting.com:2083" -ForegroundColor Yellow
Write-Host "2. MySQL Databases -> Crea BD: nexusyl_nexusmk" -ForegroundColor Yellow
Write-Host "3. Crea usuario: nexusyl_nexusmk con contrasena" -ForegroundColor Yellow
Write-Host "4. Asigna usuario a BD con TODOS LOS PRIVILEGIOS" -ForegroundColor Yellow
Write-Host "5. Abre: https://nexusmk.nexussolutionsyl.com/setup_nexusmk_db.php" -ForegroundColor Yellow
Write-Host "6. Ingresa usuario y contrasena MySQL" -ForegroundColor Yellow
Write-Host "7. El script creara tablas y datos iniciales" -ForegroundColor Yellow
Write-Host "8. Agrega las variables NEXUSMK_DB_* al .env" -ForegroundColor Yellow
Write-Host "9. ELIMINA setup_nexusmk_db.php del servidor" -ForegroundColor Red
