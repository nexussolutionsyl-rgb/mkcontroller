$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "============================================"
Write-Host "  MkController - Restart App"
Write-Host "============================================"
Write-Host ""

# 1. Deshabilitar app
Write-Host "[1] Deshabilitando app..."
try {
    $body = @{
        name = 'nexusmk'
        enabled = '0'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ App deshabilitada"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

Start-Sleep -Seconds 3

# 2. Habilitar app
Write-Host "[2] Habilitando app..."
try {
    $body = @{
        name = 'nexusmk'
        enabled = '1'
        deployment_mode = 'production'
        envvars = 'NODE_ENV=production PORT=3000 JWT_SECRET=mkcontroller_superadmin_jwt_secret_key_2024_production CORS_ORIGIN=https://nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ App habilitada"
        Write-Host "  Enabled: $($result.data.enabled)"
        Write-Host "  Deployment: $($result.data.deployment_mode)"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ❌ ERROR: $($_.Exception.Message)"
}

# 3. Esperar y probar
Write-Host "[3] Esperando 15s para que Passenger recargue..."
Start-Sleep -Seconds 15

Write-Host ""
Write-Host "  Probando app..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Web: Status $($r.StatusCode)"
    if ($r.StatusCode -eq 200) {
        Write-Host "  ✅ App funcionando!"
        $preview = $r.Content.Substring(0, [Math]::Min(500, $r.Content.Length))
        Write-Host "  Preview: $preview"
    } elseif ($r.StatusCode -eq 403) {
        Write-Host "  ⚠️ 403 - Passenger no puede iniciar la app"
        Write-Host "  Posibles causas:"
        Write-Host "    - Error en el código de la app"
        Write-Host "    - Falta de dependencias"
        Write-Host "    - Puerto incorrecto"
        Write-Host "    - Permisos insuficientes"
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

# 4. Verificar app config
Write-Host ""
Write-Host "[4] Estado final de la app..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1 -and $result.data.nexusmk) {
        $app = $result.data.nexusmk
        Write-Host "  Name: $($app.name)"
        Write-Host "  Enabled: $($app.enabled)"
        Write-Host "  Deployment: $($app.deployment_mode)"
        Write-Host "  Path: $($app.path)"
        Write-Host "  Domain: $($app.domain)"
        Write-Host "  Envvars: $($app.envvars)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
Write-Host "  Para verificar manualmente:"
Write-Host "  https://nexusmk.nexussolutionsyl.com"
Write-Host "============================================"
