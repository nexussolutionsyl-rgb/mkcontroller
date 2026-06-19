$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# 1. Registrar la aplicación Node.js
Write-Host "=== Register Application ==="
try {
    $body = @{
        name = 'nexusmk'
        path = '/home/nexusyl/nexusmk.nexussolutionsyl.com'
        domain = 'nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/register_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== List Applications ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
