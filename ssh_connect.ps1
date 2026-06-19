param(
    [string]$ComputerName = "68.65.122.57",
    [int]$Port = 21098,
    [string]$Username = "nexuyl",
    [string]$Password = "n0A3`$oDTToa4%Z7"
)

try {
    Import-Module Posh-SSH -ErrorAction Stop -WarningAction SilentlyContinue
    
    $secpass = ConvertTo-SecureString $Password -AsPlainText -Force
    $cred = New-Object System.Management.Automation.PSCredential($Username, $secpass)
    
    Write-Host "Conectando a ${Username}@${ComputerName}:${Port}..."
    
    $session = New-SSHSession -ComputerName $ComputerName -Port $Port -Credential $cred -AcceptKey -ConnectionTimeout 15 -ErrorAction Stop
    
    if ($session.Connected) {
        Write-Host "CONECTADO! SessionId: $($session.SessionId)"
        
        # Ejecutar comandos
        $commands = @(
            "echo CONECTADO",
            "pwd",
            "ls -la /home/nexuyl/nexusmk.nexussolutionsyl.com/ 2>/dev/null",
            "ls -la /home/nexuyl/public_html/nexusmk.nexussolutionsyl.com/ 2>/dev/null",
            "find /home/nexuyl -maxdepth 3 -name 'start.js' -o -name 'app.js' 2>/dev/null | head -20"
        )
        
        foreach ($cmd in $commands) {
            $result = Invoke-SSHCommand -SessionId $session.SessionId -Command $cmd
            if ($result.Output) {
                Write-Host $result.Output
            }
            if ($result.Error) {
                Write-Host "STDERR: $($result.Error)"
            }
        }
        
        Remove-SSHSession -SessionId $session.SessionId | Out-Null
        Write-Host "Sesion cerrada."
    } else {
        Write-Host "No se pudo conectar."
    }
}
catch {
    Write-Host "ERROR: $($_.Exception.Message)"
    if ($_.Exception.InnerException) {
        Write-Host "INNER: $($_.Exception.InnerException.Message)"
    }
}
