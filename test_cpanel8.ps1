$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Probar UAPI (API v3) que es más moderna - PassengerApps
Write-Host "=== UAPI PassengerApps ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_apps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Probar con diferentes nombres de módulo UAPI
$modules = @(
    'PassengerApps/list_apps',
    'PassengerApps/list_applications',
    'PassengerApps/list',
    'PassengerApps/get_applications',
    'PassengerApps/get_apps',
    'PassengerApps/status',
    'PassengerApps/show',
    'PassengerApps/index',
    'PassengerApps/fetch',
    'PassengerApps/ensure_deps',
    'PassengerApps/ensure_dependencies',
    'PassengerApps/register',
    'PassengerApps/create',
    'PassengerApps/add',
    'PassengerApps/setup',
    'PassengerApps/configure',
    'PassengerApps/config',
    'PassengerApps/settings',
    'PassengerApps/info'
)

foreach ($mod in $modules) {
    try {
        $url = "https://server166.web-hosting.com:2083/execute/$mod"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        $c = $r.Content
        if ($c.Length -gt 400) { $c = $c.Substring(0,400) + '...' }
        Write-Host "UAPI $mod : $c"
    } catch { Write-Host "UAPI $mod : ERROR - $($_.Exception.Message)" }
    Write-Host "---"
}
