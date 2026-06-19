$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Post-Deploy Setup"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Eliminar node_modules (incompatibles)
# ============================================
Write-Host "[1/5] Eliminando node_modules (versión Windows)..."

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    
    $formBody = "op=trash&sourcefiles=/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/node_modules&dirs=1"
    $formHeaders = $headers.Clone()
    $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
    
    $r = Invoke-WebRequest -Uri $url -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 30
    $result = $r.Content | ConvertFrom-Json
    Write-Host "  Resultado: $($result.cpanelresult.data[0].result)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
    Write-Host "  Continuando..."
}

# ============================================
# PASO 2: Eliminar archivos temporales
# ============================================
Write-Host "[2/5] Eliminando archivos temporales..."

# Eliminar mkcontroller.zip
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $formBody = "op=trash&sourcefiles=/home/nexusyl/nexusmk.nexussolutionsyl.com/mkcontroller.zip"
    $formHeaders = $headers.Clone()
    $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
    $r = Invoke-WebRequest -Uri $url -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
    Write-Host "  mkcontroller.zip eliminado"
} catch { Write-Host "  ERROR eliminando mkcontroller.zip: $($_.Exception.Message)" }

# Eliminar unzip.php
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $formBody = "op=trash&sourcefiles=/home/nexusyl/nexusmk.nexussolutionsyl.com/unzip.php"
    $formHeaders = $headers.Clone()
    $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
    $r = Invoke-WebRequest -Uri $url -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
    Write-Host "  unzip.php eliminado"
} catch { Write-Host "  ERROR eliminando unzip.php: $($_.Exception.Message)" }

# Eliminar test_upload.txt
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $formBody = "op=trash&sourcefiles=/home/nexusyl/nexusmk.nexussolutionsyl.com/test_upload.txt"
    $formHeaders = $headers.Clone()
    $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
    $r = Invoke-WebRequest -Uri $url -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
    Write-Host "  test_upload.txt eliminado"
} catch { Write-Host "  ERROR eliminando test_upload.txt: $($_.Exception.Message)" }

# ============================================
# PASO 3: Configurar .env
# ============================================
Write-Host "[3/5] Configurando variables de entorno..."

# Crear .env content
$envContent = @'
# MkController - Configuración de Producción
NODE_ENV=production
PORT=3000
JWT_SECRET=mkcontroller_superadmin_jwt_secret_key_2024_production
CORS_ORIGIN=https://nexusmk.nexussolutionsyl.com
# MySQL (nexusMK module)
DB_HOST=localhost
DB_USER=nexusyl
DB_PASS=n0A3$oDTToa4%Z7
DB_NAME=nexusyl_nexusmk
'@

# Subir .env usando uploadfiles
$boundary = [Guid]::NewGuid().ToString()
$lf = "`r`n"

$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines += "$remoteDir/backend"

$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines += ".env"

$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`".env`""
$bodyLines += "Content-Type: text/plain$lf"
$bodyLines += $envContent

$bodyLines += "--$boundary--"

$body = [string]::Join($lf, $bodyLines)

$multipartHeaders = $headers.Clone()
$multipartHeaders['Content-Type'] = "multipart/form-data; boundary=$boundary"

$url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir/backend"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders -Method POST -Body $body -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  .env creado exitosamente"
    } else {
        Write-Host "  ERROR creando .env: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 4: Instalar dependencias npm
# ============================================
Write-Host "[4/5] Instalando dependencias npm via PassengerApps..."

try {
    $body = @{
        type = 'npm'
        app_path = 'nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
    Write-Host "  Respuesta: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 5: Verificar estado de la app
# ============================================
Write-Host "[5/5] Verificando aplicación registrada..."

try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        foreach ($app in $result.data) {
            if ($app.name -eq 'nexusmk') {
                Write-Host "  App: $($app.name)"
                Write-Host "  Dominio: $($app.domain)"
                Write-Host "  Ruta: $($app.path)"
                Write-Host "  Deployment Mode: $($app.deployment_mode)"
                Write-Host "  Enabled: $($app.enabled)"
                Write-Host "  Environment: $($app.environment)"
            }
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
Write-Host "  Setup completado"
Write-Host "============================================"
Write-Host ""
Write-Host "Próximos pasos:"
Write-Host "  1. Esperar a que npm install termine"
Write-Host "  2. Verificar la app en: https://nexusmk.nexussolutionsyl.com"
Write-Host "  3. Configurar envvars en PassengerApps si es necesario"
