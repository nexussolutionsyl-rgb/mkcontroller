$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Buscar módulos relacionados con Node.js, Setup, Software
$tests = @(
    @{m='Software';f='list'},
    @{m='Software';f='get'},
    @{m='Software';f='get_available_versions'},
    @{m='Setup';f='list'},
    @{m='Setup';f='get'},
    @{m='Node';f='list'},
    @{m='Node';f='get'},
    @{m='Node';f='list_apps'},
    @{m='Node';f='listapps'},
    @{m='Node';f='register'},
    @{m='Node';f='register_app'},
    @{m='Passenger';f='list'},
    @{m='Passenger';f='list_apps'},
    @{m='Passenger';f='listapps'},
    @{m='Passenger';f='config'},
    @{m='Passenger';f='show_config'},
    @{m='LangPHP';f='php_get_handlers'},
    @{m='LangPHP';f='php_get_versions'},
    @{m='LangPHP';f='php_get_available_versions'},
    @{m='LangPHP';f='php_get_system_default_version'},
    @{m='LangPHP';f='php_ini_settings'},
    @{m='LangPHP';f='php_get_impact'},
    @{m='LangPHP';f='php_get_config'},
    @{m='LangPHP';f='php_set_handler'},
    @{m='LangPHP';f='php_set_ini'},
    @{m='LangPHP';f='php_set_version'},
    @{m='LangPHP';f='php_get_vhost_versions'},
    @{m='LangPHP';f='php_get_vhost_handlers'},
    @{m='LangPHP';f='php_get_domain_versions'},
    @{m='LangPHP';f='php_get_domain_handlers'}
)

foreach ($t in $tests) {
    $m = $t.m
    $f = $t.f
    try {
        $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=$m&cpanel_jsonapi_func=$f&cpanel_jsonapi_apiversion=2"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        $c = $r.Content
        if ($c.Length -gt 500) { $c = $c.Substring(0,500) + '...' }
        Write-Host "$m.$f : $c"
    } catch { Write-Host "$m.$f : ERROR - $($_.Exception.Message)" }
    Write-Host "==="
}
