$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'
$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "============================================"
Write-Host "  MkController v3.0 - Deploy via API"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Crear ZIP del proyecto
# ============================================
Write-Host "[1/6] Creando ZIP del proyecto..."

# Eliminar ZIP anterior si existe
$zipPath = "$projectRoot\mkcontroller.zip"
if (Test-Path $zipPath) { Remove-Item $zipPath }

# Crear lista de exclusión
$exclude = @('node_modules', '.git', '.idea', '*.zip', 'deploy_*.ps1', 'test_*.ps1', 'ssh-*.ps1', 'unzip.php')

# Usar Compress-Archive (PowerShell 5+)
$compressItems = @(
    "$projectRoot\backend",
    "$projectRoot\frontend",
    "$projectRoot\start.js",
    "$projectRoot\package-lock.json"
)

Compress-Archive -Path $compressItems -DestinationPath $zipPath -CompressionLevel Optimal
Write-Host "  ZIP creado: $zipPath"

# ============================================
# PASO 2: Subir unzip.php al servidor
# ============================================
Write-Host "[2/6] Subiendo script unzip.php..."

$boundary = [Guid]::NewGuid().ToString()
$lf = "`r`n"

# Leer el archivo PHP
$phpContent = [System.IO.File]::ReadAllText("$projectRoot\unzip.php")

$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines += $remoteDir

$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines += "unzip.php"

$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`"unzip.php`""
$bodyLines += "Content-Type: text/plain$lf"
$bodyLines += $phpContent

$bodyLines += "--$boundary--"

$body = [string]::Join($lf, $bodyLines)

$multipartHeaders = $headers.Clone()
$multipartHeaders['Content-Type'] = "multipart/form-data; boundary=$boundary"

$url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders -Method POST -Body $body -UseBasicParsing -TimeoutSec 30
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  unzip.php subido exitosamente"
    } else {
        Write-Host "  ERROR subiendo unzip.php: $($result.cpanelresult.error)"
        exit 1
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
    exit 1
}

# ============================================
# PASO 3: Subir ZIP al servidor
# ============================================
Write-Host "[3/6] Subiendo ZIP del proyecto..."

$boundary2 = [Guid]::NewGuid().ToString()
$zipBytes = [System.IO.File]::ReadAllBytes($zipPath)
$zipContent = [System.Text.Encoding]::GetEncoding('ISO-8859-1').GetString($zipBytes)

$bodyLines2 = @()
$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines2 += $remoteDir

$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines2 += "mkcontroller.zip"

$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"file`"; filename=`"mkcontroller.zip`""
$bodyLines2 += "Content-Type: application/zip$lf"
$bodyLines2 += $zipContent

$bodyLines2 += "--$boundary2--"

$body2 = [string]::Join($lf, $bodyLines2)

$multipartHeaders2 = $headers.Clone()
$multipartHeaders2['Content-Type'] = "multipart/form-data; boundary=$boundary2"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders2 -Method POST -Body $body2 -UseBasicParsing -TimeoutSec 60
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  ZIP subido exitosamente"
    } else {
        Write-Host "  ERROR subiendo ZIP: $($result.cpanelresult.error)"
        exit 1
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
    exit 1
}

# ============================================
# PASO 4: Ejecutar script PHP para descomprimir
# ============================================
Write-Host "[4/6] Ejecutando descompresión via PHP..."

try {
    $phpUrl = "https://nexusmk.nexussolutionsyl.com/unzip.php"
    $r = Invoke-WebRequest -Uri $phpUrl -UseBasicParsing -TimeoutSec 30
    Write-Host "  Respuesta: $($r.Content)"
} catch {
    Write-Host "  ERROR accediendo al script PHP: $($_.Exception.Message)"
    Write-Host "  Intentando con ruta alternativa..."
    
    # Intentar con la ruta directa del servidor
    try {
        $phpUrl2 = "http://server166.web-hosting.com/~nexusyl/nexusmk.nexussolutionsyl.com/unzip.php"
        $r = Invoke-WebRequest -Uri $phpUrl2 -UseBasicParsing -TimeoutSec 30
        Write-Host "  Respuesta: $($r.Content)"
    } catch {
        Write-Host "  ERROR: $($_.Exception.Message)"
    }
}

# ============================================
# PASO 5: Verificar archivos extraídos
# ============================================
Write-Host "[5/6] Verificando archivos en el servidor..."

try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  Archivos en el servidor:"
        foreach ($item in $result.data) {
            $type = if ($item.type -eq 'dir') { '[DIR]' } else { '[FILE]' }
            Write-Host "    $type $($item.file) ($($item.humansize))"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 6: Limpiar archivos temporales
# ============================================
Write-Host "[6/6] Limpieza..."

# Eliminar ZIP local
Remove-Item $zipPath -Force -ErrorAction SilentlyContinue
Write-Host "  ZIP local eliminado"

Write-Host ""
Write-Host "============================================"
Write-Host "  Proceso completado"
Write-Host "============================================"
