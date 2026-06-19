$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Funciones que ya sabemos que existen en PassengerApps:
# - list_applications ✅ (status:1)
# - ensure_deps (pide type y app_path)

# Probemos más funciones
$tests = @(
    'PassengerApps/create_app',
    'PassengerApps/create_application',
    'PassengerApps/register_app',
    'PassengerApps/register_application',
    'PassengerApps/add_app',
    'PassengerApps/add_application',
    'PassengerApps/deploy',
    'PassengerApps/deploy_app',
    'PassengerApps/start_app',
    'PassengerApps/stop_app',
    'PassengerApps/restart_app',
    'PassengerApps/remove_app',
    'PassengerApps/remove_application',
    'PassengerApps/delete_app',
    'PassengerApps/delete_application',
    'PassengerApps/unregister',
    'PassengerApps/edit_app',
    'PassengerApps/edit_application',
    'PassengerApps/update_app',
    'PassengerApps/update_application',
    'PassengerApps/get_app',
    'PassengerApps/get_application',
    'PassengerApps/app_info',
    'PassengerApps/app_details',
    'PassengerApps/env',
    'PassengerApps/environment',
    'PassengerApps/set_env',
    'PassengerApps/set_environment',
    'PassengerApps/get_env',
    'PassengerApps/get_environment'
)

foreach ($mod in $tests) {
    try {
        $url = "https://server166.web-hosting.com:2083/execute/$mod"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        $c = $r.Content
        if ($c.Length -gt 400) { $c = $c.Substring(0,400) + '...' }
        Write-Host "UAPI $mod : $c"
    } catch { Write-Host "UAPI $mod : ERROR - $($_.Exception.Message)" }
    Write-Host "---"
}
