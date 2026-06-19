$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

$funcs = @('list','list_apps','listapps','get','show','index','list_applications','listapplications','get_applications','getapplications','ensure_dependencies','register_application','register','start','stop','restart')

foreach ($f in $funcs) {
    try {
        $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=PassengerApps&cpanel_jsonapi_func=$f&cpanel_jsonapi_apiversion=2"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        Write-Host "PassengerApps.$f : $($r.Content)"
    } catch {
        Write-Host "PassengerApps.$f : ERROR - $($_.Exception.Message)"
    }
}
