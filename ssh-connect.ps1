# Script de conexión SSH a cPanel para despliegue de nexusMK
$server = "159.198.46.227"
$user = "nexuyl"
$password = "n0A3$oDTToa4%Z7"

try {
    Import-Module Posh-SSH -ErrorAction Stop
    
    Write-Host "Conectando a $server como $user..." -ForegroundColor Cyan
    
    # Crear sesión SSH
    $secpass = ConvertTo-SecureString $password -AsPlainText -Force
    $cred = New-Object System.Management.Automation.PSCredential($user, $secpass)
    
    $session = New-SSHSession -ComputerName $server -Credential $cred -AcceptKey -Force
    
    if ($session.Connected) {
        Write-Host "✅ Conectado exitosamente!" -ForegroundColor Green
        
        # Comandos iniciales para explorar el servidor
        $commands = @(
            "whoami",
            "pwd",
            "ls -la",
            "uname -a",
            "node --version 2>/dev/null || echo 'Node no instalado'",
            "npm --version 2>/dev/null || echo 'NPM no instalado'",
            "mysql --version 2>/dev/null || echo 'MySQL CLI no disponible'",
            "which node 2>/dev/null || echo 'Node no encontrado en PATH'",
            "ls -la ~/",
            "ls -la /home/nexuyl/ 2>/dev/null || echo 'No hay home'",
            "cat /etc/redhat-release 2>/dev/null || cat /etc/os-release 2>/dev/null | head -5 || echo 'OS detectado'"
        )
        
        foreach ($cmd in $commands) {
            Write-Host "`n▶ Ejecutando: $cmd" -ForegroundColor Yellow
            $result = Invoke-SSHCommand -SessionId $session.SessionId -Command $cmd
            if ($result.ExitStatus -eq 0) {
                Write-Host $result.Output -ForegroundColor White
            } else {
                Write-Host "⚠ Error (exit code: $($result.ExitStatus)): $($result.Error)" -ForegroundColor Red
            }
        }
        
        # Cerrar sesión
        Remove-SSHSession -SessionId $session.SessionId
        Write-Host "`n✅ Sesión cerrada" -ForegroundColor Green
    } else {
        Write-Host "❌ No se pudo conectar" -ForegroundColor Red
    }
} catch {
    Write-Host "❌ Error: $_" -ForegroundColor Red
}
