$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "============================================"
Write-Host "  MkController - Configure Env Vars"
Write-Host "============================================"
Write-Host ""

# Configurar envvars en PassengerApps
Write-Host "[1] Configurando envvars en PassengerApps..."

try {
    $body = @{
        name = 'nexusmk'
        envvars = 'NODE_ENV=production PORT=3000 JWT_SECRET=mkcontroller_superadmin_jwt_secret_key_2024_production CORS_ORIGIN=https://nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ Envvars configuradas exitosamente"
        Write-Host "  Deployment mode: $($result.data.deployment_mode)"
        Write-Host "  Enabled: $($result.data.enabled)"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# Verificar envvars
Write-Host ""
Write-Host "[2] Verificando envvars configuradas..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1 -and $result.data.nexusmk) {
        $app = $result.data.nexusmk
        Write-Host "  App: $($app.name)"
        Write-Host "  Envvars: $($app.envvars)"
        Write-Host "  Deployment mode: $($app.deployment_mode)"
        Write-Host "  Enabled: $($app.enabled)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# Intentar ensure_deps nuevamente
Write-Host ""
Write-Host "[3] Re-ejecutando ensure_deps..."
try {
    $body = @{
        type = 'npm'
        app_path = 'nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/ensure_deps"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ ensure_deps iniciado - Task ID: $($result.data.task_id)"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
Write-Host "  Esperando 30 segundos para npm install..."
Write-Host "============================================"
Start-Sleep -Seconds 30

# Verificar node_modules
Write-Host ""
Write-Host "[4] Verificando node_modules después de esperar..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=/home/nexusyl/nexusmk.nexussolutionsyl.com/backend"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        $hasNM = $false
        foreach ($item in $result.data) {
            if ($item.file -eq 'node_modules') { 
                $hasNM = $true
                Write-Host "  ✅ node_modules presente"
            }
        }
        if (-not $hasNM) {
            Write-Host "  ❌ node_modules aún no aparece"
        }
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# Probar la web
Write-Host ""
Write-Host "[5] Probando aplicación web..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)"
    if ($r.StatusCode -eq 200) {
        Write-Host "  ✅ App responde correctamente!"
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
Write-Host "  Verificación completada"
Write-Host "============================================"
