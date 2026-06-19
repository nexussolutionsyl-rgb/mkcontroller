# ============================================================
# SSH CONNECT - Conexion a cPanel via Posh-SSH
# ============================================================
# Requisitos: Install-Module -Name Posh-SSH -Force
# ============================================================

param(
    [string]$Command = "echo CONECTADO && pwd",
    [switch]$ListFiles,
    [switch]$Deploy
)

$ErrorActionPreference = "Stop"

$cpanel = @{
    Host     = "68.65.122.57"
    Port     = 21098
    User     = "nexusyl"
    Password = "n0A3$oDTToa4%Z7"
    Path     = "/home/nexusyl/nexusmk.nexussolutionsyl.com"
}

if (-not (Get-Module -ListAvailable -Name Posh-SSH)) {
    Write-Host "ERROR: Modulo Posh-SSH no instalado." -ForegroundColor Red
    Write-Host "Instalar: Install-Module -Name Posh-SSH -Force" -ForegroundColor Yellow
    exit 1
}
Import-Module Posh-SSH -ErrorAction Stop

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  CONECTANDO A cPanel" -ForegroundColor Cyan
Write-Host "  Host: $($cpanel.Host):$($cpanel.Port)" -ForegroundColor Cyan
Write-Host "  User: $($cpanel.User)" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

try {
    $secpass = ConvertTo-SecureString $cpanel.Password -AsPlainText -Force
    $cred = New-Object System.Management.Automation.PSCredential($cpanel.User, $secpass)

    $session = New-SSHSession -ComputerName $cpanel.Host -Port $cpanel.Port `
        -Credential $cred -AcceptKey -ConnectionTimeout 15 -ErrorAction Stop

    if (-not $session) {
        Write-Host "ERROR: No se pudo establecer la sesion SSH" -ForegroundColor Red
        exit 1
    }

    Write-Host "Conectado exitosamente" -ForegroundColor Green

    if ($ListFiles) {
        $cmd = "ls -la $($cpanel.Path)/"
        Write-Host "Listando archivos en $($cpanel.Path)..." -ForegroundColor Yellow
        $result = Invoke-SSHCommand -SessionId $session.SessionId -Command $cmd
        Write-Host $result.Output
    }
    elseif ($Deploy) {
        Write-Host "Iniciando deploy..." -ForegroundColor Yellow
        $result = Invoke-SSHCommand -SessionId $session.SessionId -Command "cd $($cpanel.Path) && ls -la"
        Write-Host $result.Output
    }
    else {
        $result = Invoke-SSHCommand -SessionId $session.SessionId -Command $Command
        Write-Host $result.Output
    }

    Remove-SSHSession -SessionId $session.SessionId | Out-Null
    Write-Host "Sesion cerrada" -ForegroundColor Green
}
catch {
    Write-Host "ERROR: $_" -ForegroundColor Red
    exit 1
}
