$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Configurar .htaccess"
Write-Host "============================================"
Write-Host ""

# Crear .htaccess que fuerce Passenger
$htaccessContent = @'
# MkController - Forzar Passenger
PassengerEnabled On
PassengerAppRoot /home/nexusyl/nexusmk.nexussolutionsyl.com
PassengerBaseURI /
PassengerNodejs /opt/alt/alt-nodejs16/root/usr/bin/node
PassengerAppType node
PassengerStartupFile passenger.js
'@

$htaccessPath = "C:\xampp2\htdocs\mk\htaccess.txt"
[System.IO.File]::WriteAllText($htaccessPath, $htaccessContent, [System.Text.Encoding]::UTF8)

Write-Host "[1] Subiendo .htaccess..."
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
    Write-Host "  Upload: $($r.Content)"
} catch {
    Write-Host "  Upload ERROR: $($_.Exception.Message)"
}

# Renombrar htaccess.txt a .htaccess
Write-Host ""
Write-Host "[2] Renombrando a .htaccess..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=rename&source-files[]=htaccess.txt&destination-files[]=.htaccess&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Rename: $($r.Content)"
} catch {
    Write-Host "  Rename ERROR: $($_.Exception.Message)"
}

# Verificar
Write-Host ""
Write-Host "[3] Verificando .htaccess..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.htaccess') {
            Write-Host "  .htaccess: ✅ presente ($($item.humansize))"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# Deshabilitar y habilitar app
Write-Host ""
Write-Host "[4] Reiniciando app Passenger..."
try {
    $body = @{name='nexusmk'; enabled='0'} | ConvertTo-Json
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  App deshabilitada"
    
    Start-Sleep -Seconds 3
    
    $body = @{name='nexusmk'; enabled='1'; deployment_mode='production'} | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  App habilitada"
    
    Start-Sleep -Seconds 20
    
    Write-Host ""
    Write-Host "  Probando app..."
    try {
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
        Write-Host "  Web: Status $($r.StatusCode)"
        if ($r.StatusCode -eq 200) {
            $content = $r.Content
            if ($content -match 'MkController|login') {
                Write-Host "  ✅ App funcionando!"
            } elseif ($content -match 'autoindex') {
                Write-Host "  ⚠️ Autoindex - Passenger no activo"
            } else {
                Write-Host "  Content: $($content.Substring(0, [Math]::Min(200, $content.Length)))"
            }
        }
    } catch {
        Write-Host "  Web: $($_.Exception.Message)"
    }
    
    try {
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -UseBasicParsing -TimeoutSec 15
        Write-Host "  API Health: $($r.StatusCode) - $($r.Content)"
    } catch {
        Write-Host "  API Health: $($_.Exception.Message)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
