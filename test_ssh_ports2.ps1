$ports = @(22, 2222, 2200, 222, 2020, 443, 7822, 22222)
foreach ($port in $ports) {
    try {
        $result = ssh -i C:\Users\Alcaldia\.ssh\nexusmk-deploy -o StrictHostKeyChecking=no -o ConnectTimeout=5 -o BatchMode=yes -p $port nexusyl@server166.web-hosting.com 'echo CONECTADO' 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Host ("PUERTO " + $port + " FUNCIONA: " + $result)
            break
        } else {
            Write-Host ("Puerto " + $port + ": NO FUNCIONA")
        }
    } catch {
        Write-Host ("Puerto " + $port + ": ERROR")
    }
}
