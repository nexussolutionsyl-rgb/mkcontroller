$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Probar ensure_deps con diferentes rutas
$paths = @(
    '/home/nexusyl/nexusmk.nexussolutionsyl.com',
    'nexusmk.nexussolutionsyl.com',
    '/nexusmk.nexussolutionsyl.com',
    './nexusmk.nexussolutionsyl.com',
    $null
)

foreach ($p in $paths) {
    try {
        $body = @{
            type = 'npm'
        }
        if ($p) { $body.app_path = $p }
        $jsonBody = $body | ConvertTo-Json
        
        $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $jsonBody -ContentType 'application/json' -UseBasicParsing -TimeoutSec 30
        Write-Host "Path '$p': $($r.Content)"
    } catch { Write-Host "Path '$p': ERROR - $($_.Exception.Message)" }
    Write-Host "---"
}

# También probar con la ruta del dominio registrado
Write-Host "`n=== Test ensure_deps con domain path ==="
try {
    $body = @{
        type = 'npm'
        app_path = 'nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $jsonBody -ContentType 'application/json' -UseBasicParsing -TimeoutSec 60
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
