# ============================================================
# 🚀 DEPLOY WEBHOOK - Sube deploy-webhook.php al servidor
# ============================================================
# Requisitos: PowerShell con módulo Posh-SSH
# ============================================================

param(
    [switch]$SetupGit,
    [switch]$TestWebhook
)

$ErrorActionPreference = "Stop"

$cpanel = @{
    Host     = "68.65.122.57"
    Port     = 21098
    User     = "nexusyl"
    Password = "n0A3$oDTToa4%Z7"
    Path     = "/home/nexusyl/nexusmk.nexussolutionsyl.com"
}

Import-Module Posh-SSH -ErrorAction Stop

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  🚀 DEPLOY WEBHOOK A cPanel" -ForegroundColor Cyan
Write-Host "  Host: $($cpanel.Host):$($cpanel.Port)" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

try {
    $secpass = ConvertTo-SecureString $cpanel.Password -AsPlainText -Force
    $cred = New-Object System.Management.Automation.PSCredential($cpanel.User, $secpass)

    $session = New-SSHSession -ComputerName $cpanel.Host -Port $cpanel.Port `
        -Credential $cred -AcceptKey -ConnectionTimeout 15 -ErrorAction Stop

    if (-not $session) {
        Write-Host "❌ No se pudo establecer la sesión SSH" -ForegroundColor Red
        exit 1
    }

    Write-Host "✅ Conectado exitosamente" -ForegroundColor Green

    # 1. Subir deploy-webhook.php
    Write-Host "[1/4] Subiendo deploy-webhook.php..." -ForegroundColor Yellow
    
    # Leer el archivo local
    $localFile = Join-Path $PSScriptRoot "deploy-webhook.php"
    $remoteFile = "$($cpanel.Path)/deploy-webhook.php"
    
    if (-not (Test-Path $localFile)) {
        Write-Host "❌ No se encuentra deploy-webhook.php localmente" -ForegroundColor Red
        exit 1
    }
    
    $content = Get-Content $localFile -Raw
    
    # Usar SCP para subir el archivo
    $scpResult = Set-SCPFile -SessionId $session.SessionId -LocalFile $localFile -RemotePath $cpanel.Path
    Write-Host "  ✅ deploy-webhook.php subido" -ForegroundColor Green
    
    # 2. Configurar permisos
    Write-Host "[2/4] Configurando permisos..." -ForegroundColor Yellow
    $permCmd = "chmod 755 $remoteFile"
    $permResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $permCmd
    Write-Host "  ✅ Permisos configurados" -ForegroundColor Green
    
    # 3. Configurar Git en el servidor (si se solicita)
    if ($SetupGit) {
        Write-Host "[3/4] Configurando Git en el servidor..." -ForegroundColor Yellow
        
        # Verificar si ya es un repo git
        $checkGit = Invoke-SSHCommand -SessionId $session.SessionId -Command "cd $($cpanel.Path) && git status 2>&1 | head -5"
        
        if ($checkGit.Output -match "not a git repository") {
            Write-Host "  Inicializando repositorio Git..." -ForegroundColor Gray
            
            # Configurar git user
            $gitUserCmd = "git config --global user.name 'MKController Deploy' && git config --global user.email 'deploy@nexussolutionsyl.com'"
            Invoke-SSHCommand -SessionId $session.SessionId -Command $gitUserCmd | Out-Null
            
            # Inicializar repo y agregar remote
            $initCmd = "cd $($cpanel.Path) && git init && git remote add origin git@github.com:nexussolutionsyl-rgb/mkcontroller.git"
            $initResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $initCmd
            Write-Host "  $($initResult.Output)"
            
            # Hacer fetch inicial
            $fetchCmd = "cd $($cpanel.Path) && git fetch origin master 2>&1"
            $fetchResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $fetchCmd
            Write-Host "  $($fetchResult.Output)"
            
            # Hacer reset
            $resetCmd = "cd $($cpanel.Path) && git reset --hard origin/master 2>&1"
            $resetResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $resetCmd
            Write-Host "  $($resetResult.Output)"
        } else {
            Write-Host "  ✅ Ya es un repositorio Git" -ForegroundColor Green
            $pullCmd = "cd $($cpanel.Path) && git pull origin master 2>&1"
            $pullResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $pullCmd
            Write-Host "  $($pullResult.Output)"
        }
        
        Write-Host "  ✅ Git configurado" -ForegroundColor Green
    } else {
        Write-Host "[3/4] Saltando configuración Git (usa -SetupGit para configurarlo)" -ForegroundColor Gray
    }
    
    # 4. Verificar el webhook
    Write-Host "[4/4] Verificando webhook..." -ForegroundColor Yellow
    $verifyCmd = "ls -la $($cpanel.Path)/deploy-webhook.php && echo '---' && head -5 $($cpanel.Path)/deploy-webhook.php"
    $verifyResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $verifyCmd
    Write-Host "  $($verifyResult.Output)"
    
    if ($TestWebhook) {
        Write-Host "  Probando webhook localmente..." -ForegroundColor Gray
        $testCmd = "cd $($cpanel.Path) && php -r 'echo php_sapi_name();' 2>&1"
        $testResult = Invoke-SSHCommand -SessionId $session.SessionId -Command $testCmd
        Write-Host "  PHP: $($testResult.Output)"
    }
    
    Remove-SSHSession -SessionId $session.SessionId | Out-Null
    Write-Host "" -ForegroundColor Cyan
    Write-Host "============================================" -ForegroundColor Cyan
    Write-Host "  ✅ WEBHOOK DEPLOYADO" -ForegroundColor Green
    Write-Host "============================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  Próximos pasos:" -ForegroundColor Yellow
    Write-Host "  1. Crear un Deploy Key en GitHub:" -ForegroundColor White
    Write-Host "     https://github.com/nexussolutionsyl-rgb/mkcontroller/settings/keys" -ForegroundColor Gray
    Write-Host "  2. Configurar Webhook en GitHub:" -ForegroundColor White
    Write-Host "     https://github.com/nexussolutionsyl-rgb/mkcontroller/settings/hooks" -ForegroundColor Gray
    Write-Host "     URL: https://nexusmk.nexussolutionsyl.com/deploy-webhook.php" -ForegroundColor Gray
    Write-Host "  3. Probar haciendo un push a master" -ForegroundColor White
    Write-Host "============================================" -ForegroundColor Cyan
}
catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
    exit 1
}
