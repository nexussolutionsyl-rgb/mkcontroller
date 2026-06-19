$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Verification"
Write-Host "============================================"
Write-Host ""

# ============================================
# PASO 1: Verificar archivos en servidor
# ============================================
Write-Host "[1/4] Verificando estructura de archivos..."

try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=$remoteDir/backend"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  Archivos en backend/:"
        $hasNodeModules = $false
        $hasEnv = $false
        foreach ($item in $result.data) {
            $type = if ($item.type -eq 'dir') { '[DIR]' } else { '[FILE]' }
            Write-Host "    $type $($item.file) ($($item.humansize))"
            if ($item.file -eq 'node_modules') { $hasNodeModules = $true }
            if ($item.file -eq '.env') { $hasEnv = $true }
        }
        Write-Host ""
        Write-Host "  node_modules: $(if($hasNodeModules){'✅'}else{'❌'})"
        Write-Host "  .env: $(if($hasEnv){'✅'}else{'❌'})"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 2: Verificar app registrada
# ============================================
Write-Host "[2/4] Verificando aplicación Passenger..."

try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        foreach ($app in $result.data) {
            Write-Host "  App: $($app.name)"
            Write-Host "  Dominio: $($app.domain)"
            Write-Host "  Ruta: $($app.path)"
            Write-Host "  Deployment Mode: $($app.deployment_mode)"
            Write-Host "  Enabled: $($app.enabled)"
            Write-Host "  Environment: $($app.environment)"
            if ($app.envvars) {
                Write-Host "  Env vars: $($app.envvars)"
            }
        }
    } else {
        Write-Host "  ERROR: $($result.errors)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 3: Probar la app via HTTP
# ============================================
Write-Host "[3/4] Probando aplicación web..."

try {
    $url = "https://nexusmk.nexussolutionsyl.com"
    $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)"
    Write-Host "  Content-Length: $($r.Headers['Content-Length'])"
    if ($r.Content.Length -gt 0) {
        Write-Host "  Primeros 500 chars: $($r.Content.Substring(0, [Math]::Min(500, $r.Content.Length)))"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# Probar API health endpoint
Write-Host ""
Write-Host "  Probando API /api/health..."
try {
    $url = "https://nexusmk.nexussolutionsyl.com/api/health"
    $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)"
    Write-Host "  Response: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# Probar nexusMK health
Write-Host ""
Write-Host "  Probando nexusMK /api/nexusmk/health..."
try {
    $url = "https://nexusmk.nexussolutionsyl.com/api/nexusmk/health"
    $r = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)"
    Write-Host "  Response: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# ============================================
# PASO 4: Configurar envvars en PassengerApps
# ============================================
Write-Host "[4/4] Configurando envvars en PassengerApps..."

try {
    $body = @{
        name = 'nexusmk'
        envvars = 'NODE_ENV=production PORT=3000 JWT_SECRET=mkcontroller_superadmin_jwt_secret_key_2024_production CORS_ORIGIN=https://nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Respuesta: $($r.Content)"
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
Write-Host "  Verificación completada"
Write-Host "============================================"
