Add-Type -AssemblyName System.Web

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Función para URL encode
function UrlEncode($s) {
    return [System.Web.HttpUtility]::UrlEncode($s)
}

# Probar savefile con GET
Write-Host "=== Test savefile ==="
try {
    $content = "console.log('test desde API');"
    $file = '/home/nexusyl/nexusmk.nexussolutionsyl.com/test.txt'
    
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel" + 
           "?cpanel_jsonapi_module=Fileman" +
           "&cpanel_jsonapi_func=savefile" +
           "&cpanel_jsonapi_apiversion=2" +
           "&file=" + [System.Web.HttpUtility]::UrlEncode($file) +
           "&content=" + [System.Web.HttpUtility]::UrlEncode($content)
    
    Write-Host "URL: $url"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Verificar si se creó el archivo
Write-Host "`n=== Verificar archivo ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=nexusmk.nexussolutionsyl.com&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
