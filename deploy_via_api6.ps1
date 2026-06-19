$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Ver el estado de la app
Write-Host "=== List Applications ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Ver archivos actualizados
Write-Host "`n=== Files in backend ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=nexusmk.nexussolutionsyl.com/backend"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Ver node_modules
Write-Host "`n=== node_modules ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=nexusmk.nexussolutionsyl.com/backend/node_modules"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
