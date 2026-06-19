$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Probar módulos comunes de cPanel
$modules = @(
    @{m='Email';f='list_pops'},
    @{m='Email';f='list_domains'},
    @{m='DomainInfo';f='list_domains'},
    @{m='DomainInfo';f='domains_data'},
    @{m='SubDomain';f='listsubdomains'},
    @{m='Fileman';f='list_files';args='?dir=nexusmk.nexussolutionsyl.com'},
    @{m='Fileman';f='get_file_content';args='?dir=nexusmk.nexussolutionsyl.com&file=package.json'},
    @{m='Mysql';f='list_databases'},
    @{m='MysqlFE';f='list_databases'},
    @{m='SSL';f='list_certs'},
    @{m='StatsBar';f='get_stats'},
    @{m='ResourceUsage';f='get_usages'},
    @{m='LangPHP';f='php_get_system_default_version'},
    @{m='Server';f='get_information'}
)

foreach ($mod in $modules) {
    $m = $mod.m
    $f = $mod.f
    $args = if ($mod.args) { $mod.args } else { '' }
    try {
        $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=$m&cpanel_jsonapi_func=$f&cpanel_jsonapi_apiversion=2$args"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        $c = $r.Content
        if ($c.Length -gt 500) { $c = $c.Substring(0,500) + '...' }
        Write-Host "$m.$f : $c"
    } catch { Write-Host "$m.$f : ERROR - $($_.Exception.Message)" }
    Write-Host "---"
}
