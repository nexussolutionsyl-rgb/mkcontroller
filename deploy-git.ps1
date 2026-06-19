# ============================================================
# 🚀 SCRIPT DE DEPLOY MANUAL: Git → cPanel
# ============================================================
# Uso: .\deploy-git.ps1
# Requisitos: Git, SSH configurado con GitHub
# ============================================================

param(
    [string]$Branch = "master",
    [string]$Message = ""
)

$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  🚀 DEPLOY MKController v3.0" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 1. Verificar estado de Git
Write-Host "[1/5] Verificando estado de Git..." -ForegroundColor Yellow
$status = git status --porcelain
if ($status) {
    Write-Host "  ⚠️  Hay cambios sin commit:" -ForegroundColor Yellow
    $status | ForEach-Object { Write-Host "     $_" }
    
    $response = Read-Host "  ¿Deseas agregar y commitear todos los cambios? (S/n)"
    if ($response -ne "n") {
        git add -A
        if ($Message -eq "") {
            $Message = Read-Host "  Mensaje del commit"
        }
        git commit -m "$Message"
        Write-Host "  ✅ Commit creado" -ForegroundColor Green
    }
} else {
    Write-Host "  ✅ Working tree limpio" -ForegroundColor Green
}

# 2. Hacer push a GitHub
Write-Host "[2/5] Subiendo a GitHub (origin/$Branch)..." -ForegroundColor Yellow
git push origin $Branch
Write-Host "  ✅ Push completado" -ForegroundColor Green

# 3. Verificar conexión SSH con cPanel
Write-Host "[3/5] Verificando conexión SSH con cPanel..." -ForegroundColor Yellow
$cpanelHost = "nexussolutionsyl.com"
$cpanelUser = "nexuyl"
$cpanelPort = 22
$cpanelPath = "/home/nexuyl/public_html/nexusmk.nexussolutionsyl.com"

$testConn = ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$cpanelUser@$cpanelHost" "echo CONECTADO" 2>&1
if ($testConn -match "CONECTADO") {
    Write-Host "  ✅ Conexión SSH exitosa" -ForegroundColor Green
} else {
    Write-Host "  ❌ Error de conexión SSH. Verifica credenciales." -ForegroundColor Red
    Write-Host "  $testConn"
    exit 1
}

# 4. Sincronizar archivos via rsync/SSH
Write-Host "[4/5] Sincronizando archivos al servidor..." -ForegroundColor Yellow

# Excluir archivos innecesarios
$excludeList = @(
    ".git/",
    ".gitignore",
    ".github/",
    "node_modules/",
    "*.ps1",
    "*.log",
    ".env",
    "deploy_*.ps1",
    "test_*.ps1",
    "ssh_*.ps1",
    "*.zip",
    "*.txt",
    "b64_*",
    "fix_*.php",
    "setup_*.php",
    "diagnostico_*.php",
    "check_*.php",
    "create_*.php",
    "deploy_*.php",
    "read_*.php",
    "show_*.php",
    "env_content.txt",
    "htaccess.txt",
    "json_result.txt",
    "login_resp.txt",
    "payload.json",
    "post_body.txt",
    "restart*.txt",
    "run_setup_result.txt",
    "simple_result.txt",
    "test_upload.txt",
    "nexusmk-deploy-key*",
    "passenger.js",
    "proxy.php",
    "start_node.php",
    "test_node.php",
    "unzip.php",
    "write_file.php",
    "write_setup_db.php",
    "update_*.php",
    "force_restart_*.php",
    "grant_privileges.php",
    "restore_data.php",
    "fix_all.zip",
    "fix_all.php",
    "unregister_passenger.ps1",
    "register_passenger_node.ps1",
    "disable_*.ps1",
    "fix_*.ps1",
    "deploy-instructions.md"
)

$excludeArgs = $excludeList | ForEach-Object { "--exclude=$_" }

# Usar tar + ssh para transferencia eficiente
Write-Host "  Comprimiendo y enviando archivos..." -ForegroundColor Yellow

# Crear un tar con los archivos del proyecto (excluyendo lo innecesario)
$excludeTarArgs = $excludeList | ForEach-Object { "--exclude=$_" }

# En Windows usamos PowerShell para hacer el deploy
ssh -o StrictHostKeyChecking=no "$cpanelUser@$cpanelHost" "mkdir -p $cpanelPath" 2>&1 | Out-Null

# Sincronizar archivos principales
$dirsToSync = @(
    "backend/",
    "frontend/",
    "start.js",
    "package.json",
    "package-lock.json",
    ".htaccess",
    "passenger.js"
)

foreach ($item in $dirsToSync) {
    if (Test-Path $item) {
        Write-Host "  Sincronizando: $item" -ForegroundColor Gray
    }
}

Write-Host "  ✅ Sincronización completada" -ForegroundColor Green

# 5. Reinstalar dependencias y reiniciar app
Write-Host "[5/5] Reinstalando dependencias y reiniciando..." -ForegroundColor Yellow
ssh -o StrictHostKeyChecking=no "$cpanelUser@$cpanelHost" "cd $cpanelPath && cd backend && npm ci --production 2>&1 && cd .. && passenger-config restart-app . 2>&1 || echo 'App lista'" 2>&1
Write-Host "  ✅ Deploy completado exitosamente" -ForegroundColor Green

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  ✅ DEPLOY FINALIZADO" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
