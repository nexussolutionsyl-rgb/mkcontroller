$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Crear .env y diagnosticar"
Write-Host "============================================"
Write-Host ""

# 1. Subir create_env.php
Write-Host "[1] Subiendo create_env.php..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    $filePath = "C:\xampp2\htdocs\mk\create_env.php"
    $boundary = [System.Guid]::NewGuid().ToString()
    $fileContent = [System.IO.File]::ReadAllBytes($filePath)
    $fileName = "create_env.php"
    
    $body = @()
    $body += "--$boundary"
    $body += "Content-Disposition: form-data; name=""file-0""; filename=""$fileName"""
    $body += "Content-Type: application/x-php"
    $body += ""
    $body += [System.Text.Encoding]::UTF8.GetString($fileContent)
    $body += "--$boundary"
    $body += "Content-Disposition: form-data; name=""dir"""
    $body += ""
    $body += $remoteDir
    $body += "--$boundary--"
    
    $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($body -join "`r`n")
    $contentType = "multipart/form-data; boundary=$boundary"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $bodyBytes -ContentType $contentType -UseBasicParsing -TimeoutSec 30
    Write-Host "  Response: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 2. Ejecutar create_env.php via HTTP
Write-Host ""
Write-Host "[2] Ejecutando create_env.php..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/create_env.php" -UseBasicParsing -TimeoutSec 15
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 3. Verificar que se crearon los .env
Write-Host ""
Write-Host "[3] Verificando .env en raiz..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.env') {
            Write-Host "  .env: ✅ presente ($($item.humansize))"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "[4] Verificando .env en backend..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir/backend&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.env') {
            Write-Host "  backend/.env: ✅ presente ($($item.humansize))"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 5. Limpiar create_env.php del servidor
Write-Host ""
Write-Host "[5] Eliminando create_env.php del servidor..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&source-files[]=create_env.php&source-files[]=unzip.php&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 6. Eliminar mk.zip
Write-Host ""
Write-Host "[6] Eliminando mk.zip..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&source-files[]=mk.zip&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Response: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 7. Deshabilitar y habilitar app para forzar recarga
Write-Host ""
Write-Host "[7] Reiniciando app Passenger..."
try {
    # Deshabilitar
    $body = @{name='nexusmk'; enabled='0'} | ConvertTo-Json
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  App deshabilitada"
    
    Start-Sleep -Seconds 3
    
    # Habilitar
    $body = @{
        name='nexusmk'
        enabled='1'
        deployment_mode='production'
    } | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  App habilitada"
    
    Start-Sleep -Seconds 15
    
    # Probar
    Write-Host ""
    Write-Host "  Probando app..."
    try {
        $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
        Write-Host "  Web: Status $($r.StatusCode)"
        if ($r.StatusCode -eq 200) {
            Write-Host "  ✅ App funcionando!"
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
