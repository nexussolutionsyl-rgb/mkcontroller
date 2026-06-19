$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Funciones que SÍ funcionaron: SubDomain.listsubdomains
# Probemos más funciones que podrían funcionar

$tests = @(
    # Fileman
    @{m='Fileman';f='listfiles';args='&dir=nexusmk.nexussolutionsyl.com'},
    @{m='Fileman';f='getfilecontent';args='&dir=nexusmk.nexussolutionsyl.com&file=package.json'},
    @{m='Fileman';f='get_file_content';args='&dir=/home/nexusyl/nexusmk.nexussolutionsyl.com&file=package.json'},
    
    # SubDomain - más funciones
    @{m='SubDomain';f='listsubdomains'},
    @{m='SubDomain';f='addsubdomain';args='&domain=nexusmk&rootdomain=nexussolutionsyl.com&dir=nexusmk.nexussolutionsyl.com'},
    
    # Mysql
    @{m='Mysql';f='listdbs'},
    @{m='Mysql';f='listdatabases'},
    @{m='Mysql';f='fe_listdatabases'},
    
    # Versiones de Node/PHP
    @{m='LangPHP';f='php_get_system_default_version'},
    @{m='LangPHP';f='php_get_available_versions'},
    
    # Información del servidor
    @{m='Server';f='serverinfo'},
    @{m='Server';f='server_information'},
    
    # Version
    @{m='Version';f='version'},
    @{m='Version';f='getversion'},
    
    # ResourceUsage
    @{m='ResourceUsage';f='get_usages'},
    @{m='ResourceUsage';f='getusages'},
    
    # StatsBar
    @{m='StatsBar';f='getstats'},
    
    # SSL
    @{m='SSL';f='listcerts'},
    @{m='SSL';f='show_cert'},
    
    # DomainInfo
    @{m='DomainInfo';f='domains_data'},
    @{m='DomainInfo';f='list_domains'},
    @{m='DomainInfo';f='main_domain_basedir'},
    
    # Email
    @{m='Email';f='listpops'},
    @{m='Email';f='listpopswithdisk'},
    
    # Park
    @{m='Park';f='list_domains'},
    @{m='Park';f='listdomains'},
    
    # AddonDomain
    @{m='AddonDomain';f='listaddondomains'},
    @{m='AddonDomain';f='list_addon_domains'}
)

foreach ($t in $tests) {
    $m = $t.m
    $f = $t.f
    $args = if ($t.args) { $t.args } else { '' }
    try {
        $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=$m&cpanel_jsonapi_func=$f&cpanel_jsonapi_apiversion=2$args"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        $c = $r.Content
        if ($c.Length -gt 600) { $c = $c.Substring(0,600) + '...' }
        Write-Host "$m.$f : $c"
    } catch { Write-Host "$m.$f : ERROR - $($_.Exception.Message)" }
    Write-Host "==="
}
