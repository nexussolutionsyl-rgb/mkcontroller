# Check server firewall configuration
param(
    [string]$Command = "echo CONECTADO"
)

$ErrorActionPreference = "Stop"

$cpanel = @{
    Host     = "68.65.122.57"
    Port     = 21098
    User     = "nexusyl"
    Password = "n0A3$oDTToa4%Z7"
}

Import-Module Posh-SSH -ErrorAction Stop

$secpass = ConvertTo-SecureString $cpanel.Password -AsPlainText -Force
$cred = New-Object System.Management.Automation.PSCredential($cpanel.User, $secpass)

$session = New-SSHSession -ComputerName $cpanel.Host -Port $cpanel.Port `
    -Credential $cred -AcceptKey -ConnectionTimeout 15 -ErrorAction Stop

if ($session) {
    Write-Host "Conectado" -ForegroundColor Green
    $result = Invoke-SSHCommand -SessionId $session.SessionId -Command $Command
    Write-Host $result.Output
    Remove-SSHSession -SessionId $session.SessionId | Out-Null
}
