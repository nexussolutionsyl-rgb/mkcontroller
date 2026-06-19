$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Passenger Fix"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Crear passenger.js (entry point para Passenger)
# ============================================
Write-Host "[1/4] Creando passenger.js (entry point)..."

$passengerJs = @'
// Entry point para Phusion Passenger (cPanel Node.js Selector)
// Passenger requiere el módulo y se encarga del listen()
require('dotenv').config({ path: './backend/.env' });
const app = require('./backend/app');
module.exports = app;
'@

$boundary = [Guid]::NewGuid().ToString()
$lf = "`r`n"
$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines += $remoteDir
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines += "passenger.js"
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`"passenger.js`""
$bodyLines += "Content-Type: application/javascript$lf"
$bodyLines += $passengerJs
$bodyLines += "--$boundary--"
$body = [string]::Join($lf, $bodyLines)

$multipartHeaders = $headers.Clone()
$multipartHeaders['Content-Type'] = "multipart/form-data; boundary=$boundary"
$url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders -Method POST -Body $body -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  ✅ passenger.js creado"
    } else {
        Write-Host "  ❌ Error: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 2: Actualizar package.json para usar passenger.js
# ============================================
Write-Host "[2/4] Actualizando package.json..."

$rootPackageJson = @'
{
  "name": "mkcontroller-app",
  "version": "3.0.0",
  "description": "MkController - MikroTik Router Administration",
  "main": "passenger.js",
  "scripts": {
    "start": "node start.js",
    "dev": "node start.js"
  },
  "dependencies": {
    "bcryptjs": "^2.4.3",
    "cors": "^2.8.5",
    "dotenv": "^16.6.1",
    "express": "^4.21.0",
    "express-rate-limit": "^7.4.1",
    "helmet": "^7.1.0",
    "jsonwebtoken": "^9.0.2",
    "mysql2": "^3.22.5",
    "node-routeros": "^1.6.9",
    "uuid": "^10.0.0",
    "ws": "^8.18.0"
  }
}
'@

$boundary2 = [Guid]::NewGuid().ToString()
$bodyLines2 = @()
$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines2 += $remoteDir
$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines2 += "package.json"
$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"file`"; filename=`"package.json`""
$bodyLines2 += "Content-Type: application/json$lf"
$bodyLines2 += $rootPackageJson
$bodyLines2 += "--$boundary2--"
$body2 = [string]::Join($lf, $bodyLines2)

$multipartHeaders2 = $headers.Clone()
$multipartHeaders2['Content-Type'] = "multipart/form-data; boundary=$boundary2"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders2 -Method POST -Body $body2 -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  ✅ package.json actualizado (main: passenger.js)"
    } else {
        Write-Host "  ❌ Error: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 3: Editar aplicación Passenger para usar passenger.js
# ============================================
Write-Host "[3/4] Configurando Passenger para usar passenger.js..."

try {
    $body = @{
        name = 'nexusmk'
        deployment_mode = 'production'
        envvars = 'NODE_ENV=production PORT=3000 JWT_SECRET=mkcontroller_superadmin_jwt_secret_key_2024_production CORS_ORIGIN=https://nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ App configurada: production mode + envvars"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 4: Verificar y probar
# ============================================
Write-Host "[4/4] Verificando..."

# Verificar archivos
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  Archivos en raíz:"
        foreach ($item in $result.data) {
            if ($item.type -eq 'file') {
                Write-Host "    - $($item.file)"
            }
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# Probar app
Write-Host ""
Write-Host "  Probando aplicación (esperar 10s para que Passenger recargue)..."
Start-Sleep -Seconds 10

try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Web: Status $($r.StatusCode)"
    if ($r.StatusCode -eq 200) {
        Write-Host "  ✅ App funcionando!"
        $preview = $r.Content.Substring(0, [Math]::Min(500, $r.Content.Length))
        Write-Host "  Preview: $preview"
    }
} catch {
    Write-Host "  Web: $($_.Exception.Message)"
}

try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -UseBasicParsing -TimeoutSec 15
    Write-Host "  API Health: $($r.StatusCode) - $($r.Content)"
} catch {
    Write-Host "  API Health: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
Write-Host "  Proceso completado"
Write-Host "============================================"
