$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# savefile con GET (parámetros en URL)
Write-Host "=== Test savefile via GET ==="
try {
    $content = "console.log('test');"
    # URL-encode the content
    $encContent = [System.Web.HttpUtility]::UrlEncode($content)
    $encFile = [System.Web.HttpUtility]::UrlEncode('/home/nexusyl/nexusmk.nexussolutionsyl.com/test.txt')
    
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&file=$encFile&content=$encContent"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# También probemos PassengerApps/edit_application para configurar deployment_mode
Write-Host "`n=== Test edit_application ==="
try {
    $body = @{
        name = 'nexusmk'
        deployment_mode = 'production'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# ensure_deps para instalar npm
Write-Host "`n=== Test ensure_deps ==="
try {
    $body = @{
        type = 'npm'
        app_path = '/home/nexusyl/nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 60
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
