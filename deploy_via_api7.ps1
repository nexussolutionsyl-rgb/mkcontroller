Add-Type -AssemblyName System.Web

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Probar savefile con POST y form-data (no JSON)
Write-Host "=== Test savefile con form-data ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel"
    
    $body = @{
        cpanel_jsonapi_module = 'Fileman'
        cpanel_jsonapi_func = 'savefile'
        cpanel_jsonapi_apiversion = '2'
        file = '/home/nexusyl/nexusmk.nexussolutionsyl.com/test2.txt'
        content = 'test desde form-data'
    }
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Verificar
Write-Host "`n=== Verificar archivos ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=nexusmk.nexussolutionsyl.com&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $data = $r.Content | ConvertFrom-Json
    foreach ($f in $data.cpanelresult.data) {
        Write-Host "$($f.file) - $($f.humansize) - modified: $(Get-Date -UnixTimeSeconds $f.mtime)"
    }
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
