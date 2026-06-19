$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "=== passenger.js ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=getfilecontent&cpanel_jsonapi_apiversion=2&path=$remoteDir/passenger.js"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data.content) {
        Write-Host $result.cpanelresult.data.content
    } else {
        Write-Host ($r.Content)
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "=== package.json (raiz) ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=getfilecontent&cpanel_jsonapi_apiversion=2&path=$remoteDir/package.json"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data.content) {
        Write-Host $result.cpanelresult.data.content
    } else {
        Write-Host ($r.Content)
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "=== backend/package.json ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=getfilecontent&cpanel_jsonapi_apiversion=2&path=$remoteDir/backend/package.json"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data.content) {
        Write-Host $result.cpanelresult.data.content
    } else {
        Write-Host ($r.Content)
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "=== backend/app.js (primeras 15 lineas) ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=getfilecontent&cpanel_jsonapi_apiversion=2&path=$remoteDir/backend/app.js"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data.content) {
        $lines = $result.cpanelresult.data.content -split "`n"
        for ($i = 0; $i -lt [Math]::Min(15, $lines.Length); $i++) {
            Write-Host ("  " + ($i+1) + ": " + $lines[$i])
        }
    } else {
        Write-Host ($r.Content)
    }
} catch {
    Write-Host "ERROR: $($_.Exception.Message)"
}
