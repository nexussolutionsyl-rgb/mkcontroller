$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Fix package.json main"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Eliminar package.json actual
# ============================================
Write-Host "[1/4] Eliminando package.json actual..."

try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $formBody = "op=trash&sourcefiles=$remoteDir/package.json"
    $formHeaders = $headers.Clone()
    $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
    $r = Invoke-WebRequest -Uri $url -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
    Write-Host "  ✅ package.json eliminado"
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 2: Subir nuevo package.json
# ============================================
Write-Host "[2/4] Subiendo nuevo package.json (main: passenger.js)..."

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

$boundary = [Guid]::NewGuid().ToString()
$lf = "`r`n"
$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines += $remoteDir
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines += "package.json"
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`"package.json`""
$bodyLines += "Content-Type: application/json$lf"
$bodyLines += $rootPackageJson
$bodyLines += "--$boundary--"
$body = [string]::Join($lf, $bodyLines)

$multipartHeaders = $headers.Clone()
$multipartHeaders['Content-Type'] = "multipart/form-data; boundary=$boundary"
$url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders -Method POST -Body $body -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  ✅ Nuevo package.json subido"
    } else {
        Write-Host "  ❌ Error: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 3: Reinstalar dependencias
# ============================================
Write-Host "[3/4] Reinstalando dependencias npm..."

try {
    $body = @{
        type = 'npm'
        app_path = 'nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ npm install iniciado"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 4: Esperar y probar
# ============================================
Write-Host "[4/4] Esperando 30s y probando..."
Start-Sleep -Seconds 30

# Verificar package.json
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        foreach ($item in $result.data) {
            if ($item.file -eq 'package.json' -or $item.file -eq 'passenger.js' -or $item.file -eq 'node_modules') {
                Write-Host "  $($item.file): ✅"
            }
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# Probar
Write-Host ""
Write-Host "  Probando app..."
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
