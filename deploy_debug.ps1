$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Debug Avanzado"
Write-Host "============================================"
Write-Host ""

# 1. Leer passenger.js del servidor (usando get_file_content o fileop)
Write-Host "[1] Leyendo passenger.js del servidor..."
try {
    # Intentar con Fileman/read_file (UAPI)
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/read_file?path=$remoteDir/passenger.js"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 2. Leer package.json del servidor
Write-Host ""
Write-Host "[2] Leyendo package.json del servidor..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/read_file?path=$remoteDir/package.json"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 3. Leer backend/package.json del servidor
Write-Host ""
Write-Host "[3] Leyendo backend/package.json del servidor..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/read_file?path=$remoteDir/backend/package.json"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 4. Leer backend/app.js del servidor (primeras líneas)
Write-Host ""
Write-Host "[4] Leyendo backend/app.js del servidor (líneas 1-5)..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/read_file?path=$remoteDir/backend/app.js&offset=0&length=200"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 5. Listar archivos en la raíz
Write-Host ""
Write-Host "[5] Listando archivos en la raíz del proyecto..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        Write-Host "  $($item.file) ($($item.humansize)) mode=$($item.nicemode)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 6. Verificar si existe node_modules
Write-Host ""
Write-Host "[6] Verificando node_modules..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir/node_modules"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    $count = ($result.cpanelresult.data | Measure-Object).Count
    Write-Host "  $count módulos en node_modules"
    # Mostrar primeros 10
    $i = 0
    foreach ($item in $result.cpanelresult.data) {
        if ($i -lt 10) {
            Write-Host "    - $($item.file)"
        }
        $i++
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 7. Verificar si existe backend/node_modules
Write-Host ""
Write-Host "[7] Verificando backend/node_modules..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir/backend/node_modules"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    $count = ($result.cpanelresult.data | Measure-Object).Count
    Write-Host "  $count módulos en backend/node_modules"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 8. Buscar logs de Passenger
Write-Host ""
Write-Host "[8] Buscando logs de Passenger..."
try {
    # Intentar listar logs en el directorio del proyecto
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -like '*passenger*' -or $item.file -like '*error*' -or $item.file -like '*log*') {
            Write-Host "  $($item.file) ($($item.humansize))"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 9. Verificar si hay un archivo .env en la raíz
Write-Host ""
Write-Host "[9] Verificando .env en raíz del proyecto..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    $found = $false
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.env') {
            Write-Host "  .env: ✅ presente en raíz ($($item.humansize))"
            $found = $true
        }
    }
    if (-not $found) {
        Write-Host "  .env: ❌ NO encontrado en raíz"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 10. Verificar si existe mk.zip (archivo temporal)
Write-Host ""
Write-Host "[10] Verificando archivos temporales..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -like '*.zip' -or $item.file -like '*env*' -or $item.file -like '*.txt') {
            Write-Host "  $($item.file) ($($item.humansize))"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
