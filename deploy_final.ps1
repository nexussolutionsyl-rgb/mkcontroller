$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Final Setup"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Verificar estado actual
# ============================================
Write-Host "[1] Verificando archivos en raíz..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        foreach ($item in $result.data) {
            $type = if ($item.type -eq 'dir') { '[DIR]' } else { '[FILE]' }
            Write-Host "  $type $($item.file) ($($item.humansize))"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 2: Subir start.js como start2.js y luego renombrar
# ============================================
Write-Host ""
Write-Host "[2] Actualizando start.js..."

$newStartJs = @'
require('dotenv').config({ path: './backend/.env' });
const app = require('./backend/app');
const db = require('./backend/models/database');
const seed = require('./backend/seed');
const config = require('./backend/config/config');

async function start() {
  try {
    console.log('╔═══════════════════════════════════════════╗');
    console.log('║        MkController v3.0.0               ║');
    console.log('║  Administración de Routers MikroTik      ║');
    console.log('╚═══════════════════════════════════════════╝');
    console.log('');

    console.log('[Init] Inicializando base de datos...');
    await db.initialize();

    console.log('[Init] Verificando datos iniciales...');
    await seed();

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

process.on('SIGINT', () => {
  console.log('\n[Server] Deteniendo servidor...');
  process.exit(0);
});

process.on('SIGTERM', () => {
  console.log('\n[Server] Deteniendo servidor...');
  process.exit(0);
});

start();
'@

# Subir como start_new.js primero
$boundary = [Guid]::NewGuid().ToString()
$lf = "`r`n"
$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines += $remoteDir
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines += "start_new.js"
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`"start_new.js`""
$bodyLines += "Content-Type: application/javascript$lf"
$bodyLines += $newStartJs
$bodyLines += "--$boundary--"
$body = [string]::Join($lf, $bodyLines)

$multipartHeaders = $headers.Clone()
$multipartHeaders['Content-Type'] = "multipart/form-data; boundary=$boundary"
$url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"

try {
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders -Method POST -Body $body -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.cpanelresult.data[0].succeeded -eq 1) {
        Write-Host "  ✅ start_new.js subido"
        
        # Renombrar: eliminar start.js y renombrar start_new.js a start.js
        Write-Host "  Renombrando archivos..."
        
        # Primero eliminar start.js
        $url2 = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
        $formBody = "op=trash&sourcefiles=$remoteDir/start.js"
        $formHeaders = $headers.Clone()
        $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
        $r2 = Invoke-WebRequest -Uri $url2 -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
        Write-Host "  start.js eliminado"
        
        # Renombrar start_new.js a start.js
        $formBody2 = "op=rename&sourcefiles=$remoteDir/start_new.js&destfiles=$remoteDir/start.js"
        $r3 = Invoke-WebRequest -Uri $url2 -Headers $formHeaders -Method POST -Body $formBody2 -UseBasicParsing -TimeoutSec 15
        Write-Host "  start_new.js renombrado a start.js"
    } else {
        Write-Host "  ❌ Error: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 3: Verificar node_modules
# ============================================
Write-Host ""
Write-Host "[3] Verificando node_modules..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir/node_modules"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        $count = ($result.data | Measure-Object).Count
        Write-Host "  node_modules contiene $count elementos"
        # Mostrar primeros 10
        $i = 0
        foreach ($item in $result.data) {
            if ($i -lt 10) {
                Write-Host "    - $($item.file)"
                $i++
            }
        }
    }
} catch {
    Write-Host "  node_modules: $($_.Exception.Message)"
}

# ============================================
# PASO 4: Probar la app
# ============================================
Write-Host ""
Write-Host "[4] Probando aplicación..."
Start-Sleep -Seconds 5

try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Web: Status $($r.StatusCode)"
    if ($r.StatusCode -eq 200) {
        Write-Host "  ✅ App funcionando!"
        $preview = $r.Content.Substring(0, [Math]::Min(500, $r.Content.Length))
        Write-Host "  Preview: $preview"
    } elseif ($r.StatusCode -eq 403) {
        Write-Host "  ⚠️ 403 - Passenger no puede iniciar la app"
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
Write-Host "  Setup completado"
Write-Host "============================================"
