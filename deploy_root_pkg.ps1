$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Fix Root package.json"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Crear package.json para la raíz
# ============================================
Write-Host "[1/4] Creando package.json para la raíz..."

$rootPackageJson = @'
{
  "name": "mkcontroller-app",
  "version": "3.0.0",
  "description": "MkController - MikroTik Router Administration",
  "main": "start.js",
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

# Subir package.json a la raíz
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
        Write-Host "  ✅ package.json creado en raíz"
    } else {
        Write-Host "  ❌ Error: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 2: Actualizar start.js para cargar dotenv
# ============================================
Write-Host "[2/4] Actualizando start.js para soportar dotenv..."

$newStartJs = @'
require('dotenv').config({ path: './backend/.env' });
const app = require('./backend/app');
const db = require('./backend/models/database');
const seed = require('./backend/seed');
const config = require('./backend/config/config');

/**
 * Punto de entrada de MkController
 * Inicializa la base de datos, ejecuta seed y arranca el servidor
 */
async function start() {
  try {
    console.log('╔═══════════════════════════════════════════╗');
    console.log('║        MkController v3.0.0               ║');
    console.log('║  Administración de Routers MikroTik      ║');
    console.log('╚═══════════════════════════════════════════╝');
    console.log('');

    // Inicializar base de datos
    console.log('[Init] Inicializando base de datos...');
    await db.initialize();

    // Ejecutar seed (crear datos iniciales si no existen)
    console.log('[Init] Verificando datos iniciales...');
    await seed();

    // Iniciar servidor
    const PORT = config.port;
    app.listen(PORT, () => {
      console.log('');
      console.log(`[Server] MkController corriendo en puerto ${PORT}`);
      console.log('');
      console.log('[Server] Presione Ctrl+C para detener');
    });
  } catch (error) {
    console.error('[Init] Error fatal:', error);
    process.exit(1);
  }
}

// Manejar señales de terminación
process.on('SIGINT', () => {
  console.log('\n[Server] Deteniendo servidor...');
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('\n[Server] Deteniendo servidor...');
  process.exit(0);
});

// Iniciar
start();
'@

$boundary2 = [Guid]::NewGuid().ToString()
$bodyLines2 = @()
$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines2 += $remoteDir

$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines2 += "start.js"

$bodyLines2 += "--$boundary2"
$bodyLines2 += "Content-Disposition: form-data; name=`"file`"; filename=`"start.js`""
$bodyLines2 += "Content-Type: application/javascript$lf"
$bodyLines2 += $newStartJs

$bodyLines2 += "--$boundary2--"

$body2 = [string]::Join($lf, $bodyLines2)

$multipartHeaders2 = $headers.Clone()
$multipartHeaders2['Content-Type'] = "multipart/form-data; boundary=$boundary2"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders2 -Method POST -Body $body2 -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  ✅ start.js actualizado"
    } else {
        Write-Host "  ❌ Error: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 3: Instalar dependencias npm
# ============================================
Write-Host "[3/4] Instalando dependencias npm..."

try {
    $body = @{
        type = 'npm'
        app_path = 'nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ npm install iniciado - Task ID: $($result.data.task_id)"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 4: Esperar y verificar
# ============================================
Write-Host "[4/4] Esperando 45 segundos para npm install..."
Start-Sleep -Seconds 45

Write-Host "  Verificando node_modules en raíz..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        $hasNM = $false
        $hasPkg = $false
        foreach ($item in $result.data) {
            if ($item.file -eq 'node_modules') { $hasNM = $true }
            if ($item.file -eq 'package.json') { $hasPkg = $true }
        }
        Write-Host "  node_modules: $(if($hasNM){'✅'}else{'❌'})"
        Write-Host "  package.json: $(if($hasPkg){'✅'}else{'❌'})"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# Probar web
Write-Host ""
Write-Host "  Probando aplicación..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)"
    if ($r.StatusCode -eq 200) {
        Write-Host "  ✅ App funcionando!"
        $preview = $r.Content.Substring(0, [Math]::Min(300, $r.Content.Length))
        Write-Host "  Preview: $preview"
    }
} catch {
    Write-Host "  Status: $($_.Exception.Message)"
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
