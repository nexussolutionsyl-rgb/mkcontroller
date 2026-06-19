$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Fix .env file"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Subir env.txt y renombrar a .env
# ============================================
Write-Host "[1/4] Creando archivo env.txt con contenido..."

$envContent = @'
NODE_ENV=production
PORT=3000
JWT_SECRET=mkcontroller_superadmin_jwt_secret_key_2024_production
CORS_ORIGIN=https://nexusmk.nexussolutionsyl.com
DB_HOST=localhost
DB_USER=nexusyl
DB_PASS=n0A3$oDTToa4%Z7
DB_NAME=nexusyl_nexusmk
'@

$boundary = [Guid]::NewGuid().ToString()
$lf = "`r`n"
$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"dir`"$lf"
$bodyLines += "$remoteDir/backend"
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"filename`"$lf"
$bodyLines += "env.txt"
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`"env.txt`""
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
        Write-Host "  ✅ env.txt subido"
        
        # Renombrar env.txt a .env
        Write-Host "  Renombrando a .env..."
        $url2 = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
        $formBody = "op=rename&sourcefiles=$remoteDir/backend/env.txt&destfiles=$remoteDir/backend/.env"
        $formHeaders = $headers.Clone()
        $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
        $r2 = Invoke-WebRequest -Uri $url2 -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
        Write-Host "  ✅ .env creado exitosamente"
    } else {
        Write-Host "  ❌ Error: $($result.cpanelresult.error)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 2: Verificar .env
# ============================================
Write-Host "[2/4] Verificando .env..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir/backend&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    $found = $false
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.env') {
            Write-Host "  .env: ✅ presente ($($item.humansize))"
            $found = $true
        }
        if ($item.file -eq 'env.txt') {
            Write-Host "  env.txt: ⚠️ aún presente"
        }
    }
    if (-not $found) {
        Write-Host "  .env: ❌ no encontrado"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
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
Write-Host "[4/4] Esperando 40s y probando..."
Start-Sleep -Seconds 40

Write-Host "  Verificando node_modules..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir/node_modules"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        $count = ($result.data | Measure-Object).Count
        Write-Host "  node_modules: ✅ $count módulos"
    }
} catch {
    Write-Host "  node_modules: ❌ $($_.Exception.Message)"
}

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
