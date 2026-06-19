$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Probar funciones de Fileman para subir archivos
$tests = @(
    @{m='Fileman';f='uploadfiles'},
    @{m='Fileman';f='upload_file'},
    @{m='Fileman';f='fileupload'},
    @{m='Fileman';f='save_file_content'},
    @{m='Fileman';f='savefilecontent'},
    @{m='Fileman';f='save_file'},
    @{m='Fileman';f='savefile'},
    @{m='Fileman';f='edit_file'},
    @{m='Fileman';f='editfile'},
    @{m='Fileman';f='mkdir'},
    @{m='Fileman';f='mkdirmode'},
    @{m='Fileman';f='copy'},
    @{m='Fileman';f='copyfile'},
    @{m='Fileman';f='move'},
    @{m='Fileman';f='movefile'},
    @{m='Fileman';f='rename'},
    @{m='Fileman';f='renamefile'},
    @{m='Fileman';f='delete'},
    @{m='Fileman';f='deletefile'},
    @{m='Fileman';f='extract'},
    @{m='Fileman';f='extractfile'},
    @{m='Fileman';f='zip'},
    @{m='Fileman';f='zipfiles'},
    @{m='Fileman';f='unzip'},
    @{m='Fileman';f='unzip_files'}
)

foreach ($t in $tests) {
    $m = $t.m
    $f = $t.f
    try {
        $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=$m&cpanel_jsonapi_func=$f&cpanel_jsonapi_apiversion=2"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
        $c = $r.Content
        if ($c.Length -gt 400) { $c = $c.Substring(0,400) + '...' }
        Write-Host "$m.$f : $c"
    } catch { Write-Host "$m.$f : ERROR - $($_.Exception.Message)" }
    Write-Host "==="
}
