$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Probar UAPI (API v3) para Node.js
Write-Host "=== UAPI: NodeApps ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/NodeApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "=== UAPI: Passenger ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/Passenger/list_apps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Listar módulos disponibles
Write-Host "=== UAPI: Versions ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/Version/version"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Probar con ApplicationManager
Write-Host "=== UAPI: ApplicationManager ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/ApplicationManager/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Buscar qué módulos existen
Write-Host "=== API v2: GetModules ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=PassengerApps&cpanel_jsonapi_func=GetModules&cpanel_jsonapi_apiversion=2"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Probar con NodeJS
Write-Host "=== UAPI: NodeJS ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/NodeJS/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
