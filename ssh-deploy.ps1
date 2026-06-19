# Script de despliegue SSH para nexusMK en cPanel
param(
    [string]$Server = "159.198.46.227",
    [string]$User = "nexuyl",
    [string]$Password = "n0A3$oDTToa4%Z7"
)

Write-Host "=== Despliegue nexusMK en cPanel ===" -ForegroundColor Cyan
Write-Host "Servidor: $Server" -ForegroundColor Cyan
Write-Host "Usuario: $User" -ForegroundColor Cyan

# Función para ejecutar comando SSH con contraseña
function Invoke-SSHCommand {
    param([string]$Command)
    
    # Usar sshpass si está disponible, si no, usar enfoque alternativo
    $sshpassPath = Get-Command "sshpass" -ErrorAction SilentlyContinue
    if ($sshpassPath) {
        $fullCmd = "sshpass -p '$Password' ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 $User@$Server '$Command' 2>&1"
        $result = cmd /c $fullCmd
        return $result
    } else {
        # Usar ssh con entrada por pipe
        $secpass = ConvertTo-SecureString $Password -AsPlainText -Force
        $cred = New-Object System.Management.Automation.PSCredential($User, $secpass)
        
        # Intentar con ssh directamente (pedirá password)
        $result = ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$User@$Server" "$Command" 2>&1
        return $result
    }
}

Write-Host "`n[1/8] Verificando conexión SSH..." -ForegroundColor Yellow
try {
    $result = ssh -o StrictHostKeyChecking=no -o ConnectTimeout=10 "$User@$Server" "echo CONECTADO" 2>&1
    Write-Host "Resultado: $result" -ForegroundColor White
} catch {
    Write-Host "⚠ SSH requiere autenticación interactiva" -ForegroundColor Red
    Write-Host "`nIMPORTANTE: Para continuar, necesitas ejecutar manualmente:" -ForegroundColor Yellow
    Write-Host "ssh $User@$Server" -ForegroundColor Green
    Write-Host "`nLuego ejecuta estos comandos para explorar el servidor:" -ForegroundColor Cyan
}

Write-Host "`n=== COMANDOS PARA EJECUTAR EN EL SERVIDOR ===" -ForegroundColor Magenta
Write-Host @"

# 1. Verificar entorno
whoami
pwd
ls -la
node --version
npm --version
mysql --version
which node

# 2. Ver estructura de directorios
ls -la /home/$User/
ls -la /home/$User/public_html/ 2>/dev/null || echo "No public_html"

# 3. Verificar si existe Node.js en cPanel
ls -la /opt/cpanel/ 2>/dev/null || echo "No cpanel"
which nodejs 2>/dev/null || echo "No nodejs"

# 4. Verificar gestor de procesos (passenger, etc)
which passenger 2>/dev/null || echo "No passenger"
ls -la /etc/nginx/conf.d/ 2>/dev/null || echo "No nginx conf"
ls -la /etc/httpd/conf.d/ 2>/dev/null || echo "No httpd conf"

"@ -ForegroundColor White

Write-Host "`n=== INSTRUCCIONES DE CONEXIÓN MANUAL ===" -ForegroundColor Green
Write-Host "Abre una terminal y ejecuta:" -ForegroundColor White
Write-Host "ssh $User@$Server" -ForegroundColor Yellow
Write-Host "`nLuego ingresa la contraseña de cPanel cuando te la pida." -ForegroundColor White
