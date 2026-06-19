# ============================================
# Script de despliegue de correcciones via cPanel API
# Usa Fileman::uploadfiles con Credential de PowerShell
# ============================================

$cpanelUser = "nexusyl"
$cpanelPass = "n0A3$oDTToa4%Z7"
$baseUrl = "https://server166.web-hosting.com:2083"
$remoteDir = "/home/nexusyl/nexusmk.nexussolutionsyl.com"
$localBase = "C:\xampp2\htdocs\mk"

# Credenciales de PowerShell (NO manual Basic Auth header)
$securePass = ConvertTo-SecureString $cpanelPass -AsPlainText -Force
$credential = New-Object System.Management.Automation.PSCredential($cpanelUser, $securePass)

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Despliegue de Correcciones via cPanel API" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

$uploadUrl = "${baseUrl}/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"

# Funcion para subir archivo via multipart
function Upload-File($remotePath, $localPath) {
    if (-not (Test-Path $localPath)) {
        Write-Host "  $remotePath : archivo local NO encontrado" -ForegroundColor Yellow
        return $false
    }
    
    $fileContent = [System.IO.File]::ReadAllText((Resolve-Path $localPath))
    $fileName = [System.IO.Path]::GetFileName($remotePath)
    $remoteDirOnly = [System.IO.Path]::GetDirectoryName($remotePath) -replace '\\', '/'
    
    $boundary = "----Boundary" + [Guid]::NewGuid().ToString("N")
    $bodyLines = @()
    
    # dir parameter
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="dir"'
    $bodyLines += ""
    $bodyLines += $remoteDirOnly
    
    # file-0 parameter
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"$fileName`""
    $bodyLines += "Content-Type: application/octet-stream"
    $bodyLines += ""
    $bodyLines += $fileContent
    $bodyLines += "--$boundary--"
    
    $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes(($bodyLines -join "`r`n"))
    
    try {
        $r = Invoke-WebRequest -Uri $uploadUrl -Method POST -Credential $credential -ContentType "multipart/form-data; boundary=$boundary" -Body $bodyBytes -UseBasicParsing -TimeoutSec 60
        $result = $r.Content | ConvertFrom-Json
        if ($result.cpanelresult.error) {
            Write-Host "  $remotePath : $($result.cpanelresult.error)" -ForegroundColor Red
            return $false
        } else {
            Write-Host "  $remotePath" -ForegroundColor Green
            return $true
        }
    } catch {
        Write-Host "  $remotePath : $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# ============================================
# PASO 1: Subir archivos corregidos
# ============================================
Write-Host "PASO 1: Subiendo archivos corregidos..." -ForegroundColor Yellow
Write-Host ""

$files = @(
    @{remote="backend/controllers/authController.js"; local="backend/controllers/authController.js"},
    @{remote="backend/config/config.js"; local="backend/config/config.js"},
    @{remote="backend/services/mikrotikService.js"; local="backend/services/mikrotikService.js"},
    @{remote="backend/controllers/nexusmkController.js"; local="backend/controllers/nexusmkController.js"},
    @{remote="start.js"; local="start.js"}
)

$successCount = 0
$failCount = 0

foreach ($f in $files) {
    $remotePath = "$remoteDir/$($f.remote)"
    $localPath = "$localBase/$($f.local)"
    $result = Upload-File $remotePath $localPath
    if ($result) { $successCount++ } else { $failCount++ }
}

Write-Host ""
Write-Host "Resultado: $successCount subidos, $failCount fallos" -ForegroundColor Cyan

# ============================================
# PASO 2: Crear .env via PHP intermedio
# ============================================
Write-Host ""
Write-Host "PASO 2: Creando .env con credenciales MySQL..." -ForegroundColor Yellow
Write-Host ""

$envContent = @"
# MkController - Variables de Entorno
PORT=3000
JWT_SECRET=MkController2024_SuperSecretKey_ChangeInProduction
JWT_EXPIRES_IN=24h
MYSQL_HOST=localhost
MYSQL_USER=nexusyl_nexusmk
MYSQL_PASSWORD=n0A3$oDTToa4%Z7
MYSQL_DATABASE=nexusyl_nexusmk
NEXUSMK_DB_HOST=localhost
NEXUSMK_DB_USER=nexusyl_nexusmk
NEXUSMK_DB_PASSWORD=n0A3$oDTToa4%Z7
NEXUSMK_DB_NAME=nexusyl_nexusmk
"@

$b64Env = [System.Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($envContent))

$phpScript = @"
<?php
`$content = base64_decode('$b64Env');
file_put_contents('/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/.env', `$content);
echo "OK: " . filesize('/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/.env') . " bytes";
"@

# Subir write_env.php
$boundary = "----Boundary" + [Guid]::NewGuid().ToString("N")
$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += 'Content-Disposition: form-data; name="dir"'
$bodyLines += ""
$bodyLines += $remoteDir
$bodyLines += "--$boundary"
$bodyLines += 'Content-Disposition: form-data; name="file-0"; filename="write_env.php"'
$bodyLines += "Content-Type: application/x-php"
$bodyLines += ""
$bodyLines += $phpScript
$bodyLines += "--$boundary--"
$bodyBytes = [System.Text.Encoding]::UTF8.GetBytes(($bodyLines -join "`r`n"))

try {
    $r = Invoke-WebRequest -Uri $uploadUrl -Method POST -Credential $credential -ContentType "multipart/form-data; boundary=$boundary" -Body $bodyBytes -UseBasicParsing -TimeoutSec 60
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.error) {
        Write-Host "  write_env.php : $($result.cpanelresult.error)" -ForegroundColor Yellow
    } else {
        Write-Host "  write_env.php subido, ejecutando..." -ForegroundColor Green
        try {
            $execResponse = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/write_env.php" -TimeoutSec 30
            Write-Host "  Resultado: $($execResponse.Content)" -ForegroundColor Green
        } catch {
            Write-Host "  Error ejecutando write_env.php: $($_.Exception.Message)" -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "  write_env.php : $($_.Exception.Message)" -ForegroundColor Yellow
}

# ============================================
# PASO 3: Verificar despliegue
# ============================================
Write-Host ""
Write-Host "PASO 3: Verificando despliegue..." -ForegroundColor Yellow
Write-Host ""

$checkPhp = @"
<?php
`$files = [
    'start.js',
    'backend/controllers/authController.js',
    'backend/config/config.js',
    'backend/services/mikrotikService.js',
    'backend/controllers/nexusmkController.js',
    'backend/.env'
];
echo "<h2>Verificacion de archivos</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Archivo</th><th>Tamanio</th><th>Fecha</th></tr>";
foreach (`$f as `$file) {
    `$path = '/home/nexusyl/nexusmk.nexussolutionsyl.com/' . `$file;
    if (file_exists(`$path)) {
        echo "<tr><td>`$file</td><td>" . filesize(`$path) . " bytes</td><td>" . date('Y-m-d H:i:s', filemtime(`$path)) . "</td></tr>";
    } else {
        echo "<tr><td>`$file</td><td colspan='2'>NO ENCONTRADO</td></tr>";
    }
}
echo "</table>";
"@

$boundary = "----Boundary" + [Guid]::NewGuid().ToString("N")
$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += 'Content-Disposition: form-data; name="dir"'
$bodyLines += ""
$bodyLines += $remoteDir
$bodyLines += "--$boundary"
$bodyLines += 'Content-Disposition: form-data; name="file-0"; filename="check_deploy.php"'
$bodyLines += "Content-Type: application/x-php"
$bodyLines += ""
$bodyLines += $checkPhp
$bodyLines += "--$boundary--"
$bodyBytes = [System.Text.Encoding]::UTF8.GetBytes(($bodyLines -join "`r`n"))

try {
    $r = Invoke-WebRequest -Uri $uploadUrl -Method POST -Credential $credential -ContentType "multipart/form-data; boundary=$boundary" -Body $bodyBytes -UseBasicParsing -TimeoutSec 60
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.error) {
        Write-Host "  check_deploy.php : $($result.cpanelresult.error)" -ForegroundColor Yellow
    } else {
        Write-Host "  check_deploy.php subido" -ForegroundColor Green
    }
} catch {
    Write-Host "  check_deploy.php : $($_.Exception.Message)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "IMPORTANTE: Para que los cambios surtan efecto:" -ForegroundColor Yellow
Write-Host "  1. Vaya a cPanel > Node.js Selector" -ForegroundColor Yellow
Write-Host "  2. Detenga la aplicacion y vuelva a iniciarla" -ForegroundColor Yellow
Write-Host "  3. O visite: https://nexusmk.nexussolutionsyl.com/check_deploy.php" -ForegroundColor Cyan
Write-Host "     Para verificar que los archivos se actualizaron." -ForegroundColor Cyan

# ============================================
# RESUMEN FINAL
# ============================================
Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  RESUMEN DEL DESPLIEGUE" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Archivos a subir:" -ForegroundColor White
Write-Host "  1. backend/controllers/authController.js - Pool MySQL + finally blocks" -ForegroundColor Gray
Write-Host "  2. backend/config/config.js - Seccion mysql centralizada" -ForegroundColor Gray
Write-Host "  3. backend/services/mikrotikService.js - Limpieza conexiones stale" -ForegroundColor Gray
Write-Host "  4. backend/controllers/nexusmkController.js - Pool + whitelist SQL" -ForegroundColor Gray
Write-Host "  5. start.js - Bug config.server.port corregido" -ForegroundColor Gray
Write-Host "  6. backend/.env - Credenciales MySQL configuradas" -ForegroundColor Gray
Write-Host ""
Write-Host "Correcciones:" -ForegroundColor White
Write-Host "  Login: authController ahora usa pool MySQL con credenciales correctas" -ForegroundColor Green
Write-Host "  Login: Ya no abre 2 conexiones separadas, reusa la misma" -ForegroundColor Green
Write-Host "  Login: Bloques finally liberan conexiones siempre" -ForegroundColor Green
Write-Host "  MikroTik: Conexiones stale se limpian automaticamente cada 5 min" -ForegroundColor Green
Write-Host "  MikroTik: Verificacion real de conectividad antes de reusar conexion" -ForegroundColor Green
Write-Host "  nexusMK: Pool de conexiones singleton con finally blocks" -ForegroundColor Green
Write-Host "  nexusMK: Proteccion anti-inyeccion SQL con lista blanca de tablas" -ForegroundColor Green
Write-Host "  start.js: Puerto ahora se lee correctamente de config.js" -ForegroundColor Green
Write-Host ""
Write-Host "NOTA: Si el login automatico persiste (sin pulsar boton)," -ForegroundColor Yellow
Write-Host "limpiar localStorage del navegador o hacer logout explicito." -ForegroundColor Yellow
Write-Host "El token anterior sigue siendo valido hasta su expiracion (24h)." -ForegroundColor Yellow
Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
