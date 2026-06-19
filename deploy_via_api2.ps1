$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Probar savefile con más detalle
Write-Host "=== Test savefile ==="
try {
    $content = "console.log('test');"
    $body = @{
        file = '/home/nexusyl/nexusmk.nexussolutionsyl.com/test.txt'
        content = $content
    } | ConvertTo-Json
    
    Write-Host "Body: $body"
    
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Probar con uploadfiles
Write-Host "`n=== Test uploadfiles ==="
try {
    # Primero necesitamos el contenido del archivo en base64
    $fileContent = [System.IO.File]::ReadAllBytes("C:\xampp2\htdocs\mk\start.js")
    $base64Content = [System.Convert]::ToBase64String($fileContent)
    
    $body = @{
        dir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'
        files = @(
            @{
                name = 'start.js'
                file = $base64Content
            }
        )
    } | ConvertTo-Json -Depth 5
    
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 30
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
