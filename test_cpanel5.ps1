$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Ver archivos completos en nexusmk
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=nexusmk.nexussolutionsyl.com&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $c = $r.Content
    Write-Host "=== Files in nexusmk.nexussolutionsyl.com ==="
    Write-Host $c
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== Intentando SSH con clave ==="
