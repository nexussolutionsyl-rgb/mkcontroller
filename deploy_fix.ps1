$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Fix & Reinstall"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Verificar .env (archivos ocultos)
# ============================================
Write-Host "[1/5] Verificando .env (archivos ocultos)..."

try {
    # Listar con dir=/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/ y mostrar todos
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=/home/nexusyl/nexusmk.nexussolutionsyl.com/backend&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    $hasEnv = $false
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.env') {
            $hasEnv = $true
            Write-Host "  .env encontrado: $($item.humansize)"
        }
    }
    if (-not $hasEnv) {
        Write-Host "  .env NO encontrado - recreando..."
        
        # Re-crear .env
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
        $bodyLines += ".env"
        $bodyLines += "--$boundary"
        $bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`".env`""
        $bodyLines += "Content-Type: text/plain$lf"
        $bodyLines += $envContent
        $bodyLines += "--$boundary--"
        $body = [string]::Join($lf, $bodyLines)
        
        $multipartHeaders = $headers.Clone()
        $multipartHeaders['Content-Type'] = "multipart/form-data; boundary=$boundary"
        $url2 = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir/backend"
        $r = Invoke-WebRequest -Uri $url2 -Headers $multipartHeaders -Method POST -Body $body -UseBasicParsing -TimeoutSec 15
        Write-Host "  .env recreado"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 2: Verificar si existe package-lock.json viejo
# ============================================
Write-Host "[2/5] Verificando package-lock.json en raíz..."

try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        foreach ($item in $result.data) {
            if ($item.file -eq 'package-lock.json' -and $item.type -eq 'file') {
                Write-Host "  package-lock.json en raíz: $($item.humansize) - debe eliminarse"
                
                # Eliminarlo
                $url2 = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
                $formBody = "op=trash&sourcefiles=$remoteDir/package-lock.json"
                $formHeaders = $headers.Clone()
                $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
                $r2 = Invoke-WebRequest -Uri $url2 -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
                Write-Host "  package-lock.json eliminado"
            }
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 3: Reinstalar dependencias npm
# ============================================
Write-Host "[3/5] Reinstalando dependencias npm..."

try {
    $body = @{
        type = 'npm'
        app_path = 'nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  npm install iniciado - Task ID: $($result.data.task_id)"
        Write-Host "  SSE URL: $($result.data.sse_url)"
    } else {
        Write-Host "  ERROR: $($result.errors)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 4: Esperar y verificar node_modules
# ============================================
Write-Host "[4/5] Esperando 15 segundos para npm install..."
Start-Sleep -Seconds 15

Write-Host "  Verificando node_modules..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir/backend"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        $hasNM = $false
        foreach ($item in $result.data) {
            if ($item.file -eq 'node_modules') {
                $hasNM = $true
                Write-Host "  node_modules: ✅ presente"
            }
        }
        if (-not $hasNM) {
            Write-Host "  node_modules: ❌ aún no aparece"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 5: Probar la app nuevamente
# ============================================
Write-Host "[5/5] Probando aplicación web..."

try {
    $url = "https://nexusmk.nexussolutionsyl.com"
    $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)"
    if ($r.Content.Length -gt 0) {
        Write-Host "  Primeros 300 chars: $($r.Content.Substring(0, [Math]::Min(300, $r.Content.Length)))"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

try {
    $url = "https://nexusmk.nexussolutionsyl.com/api/health"
    $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 15
    Write-Host "  API Health: $($r.StatusCode) - $($r.Content)"
} catch {
    Write-Host "  API Health: ERROR - $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
Write-Host "  Proceso completado"
Write-Host "============================================"
