$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Diagnóstico"
Write-Host "============================================"
Write-Host ""

# 1. Ver app config detallada
Write-Host "[1] App Passenger - Config detallada..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 2. Verificar permisos de archivos
Write-Host ""
Write-Host "[2] Verificando permisos de archivos clave..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -in @('package.json', 'passenger.js', 'start.js', 'backend')) {
            Write-Host "  $($item.file): mode=$($item.nicemode) uid=$($item.uid) gid=$($item.gid)"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 3. Verificar si existe .env en backend
Write-Host ""
Write-Host "[3] Verificando .env en backend..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir/backend&showhidden=1"
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

# 4. Verificar si passenger.js tiene el contenido correcto
Write-Host ""
Write-Host "[4] Leyendo passenger.js..."
try {
    # Usar API v2 para leer archivo
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=getfilecontent&cpanel_jsonapi_apiversion=2&path=$remoteDir/passenger.js"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "  $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 5. Probar con curl-like para ver el error exacto
Write-Host ""
Write-Host "[5] Probando con headers para ver error detallado..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15 -ErrorVariable webError
    Write-Host "  Status: $($r.StatusCode)"
    Write-Host "  Headers:"
    foreach ($key in $r.Headers.Keys) {
        Write-Host "    $key : $($r.Headers[$key])"
    }
} catch {
    Write-Host "  Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        try {
            $reader = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd()
            $reader.Close()
            Write-Host "  Response body: $responseBody"
        } catch {}
    }
}

# 6. Verificar si el dominio apunta correctamente
Write-Host ""
Write-Host "[6] Verificando DNS/subdominio..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/SubDomain/listsubdomains"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        foreach ($sub in $result.data) {
            if ($sub.domain -like '*nexusmk*') {
                Write-Host "  Subdominio: $($sub.domain)"
                Write-Host "  Document Root: $($sub.documentroot)"
            }
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
