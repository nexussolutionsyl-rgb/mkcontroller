#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$baseUrl = "https://server166.web-hosting.com:2083"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Registrar Passenger App con Node.js" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# 1. Listar apps actuales
Write-Host "[1] Listando PassengerApps actuales..." -ForegroundColor Yellow
try {
    $url = "$baseUrl/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    $data = $r.Content | ConvertFrom-Json
    if ($data.data) {
        foreach ($app in $data.data) {
            Write-Host "  App: name=$($app.name) domain=$($app.domain) path=$($app.path) enabled=$($app.enabled)" -ForegroundColor Gray
            Write-Host "    ruby=$($app.ruby) python=$($app.python) nodejs=$($app.nodejs)" -ForegroundColor Gray
        }
    } else {
        Write-Host "  No apps found or error: $($r.Content)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "  Error: $_" -ForegroundColor Red
}

Write-Host ""

# 2. Si existe nexusmk, eliminarla
Write-Host "[2] Eliminando app nexusmk si existe..." -ForegroundColor Yellow
try {
    $url = "$baseUrl/execute/PassengerApps/unregister_application"
    $body = "name=nexusmk"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Result: $($r.Content)" -ForegroundColor Gray
} catch {
    Write-Host "  Error (puede no existir): $_" -ForegroundColor Yellow
}

Start-Sleep -Seconds 3

# 3. Registrar la app con Node.js
Write-Host ""
Write-Host "[3] Registrando app nexusmk con Node.js..." -ForegroundColor Yellow
try {
    $url = "$baseUrl/execute/PassengerApps/register_application"
    
    # Construir el body como form-urlencoded
    $body = @{
        name = "nexusmk"
        domain = "nexusmk.nexussolutionsyl.com"
        path = "/home/nexusyl/nexusmk.nexussolutionsyl.com"
        deployment_mode = "production"
        enabled = "1"
    }
    
    $bodyString = "name=nexusmk&domain=nexusmk.nexussolutionsyl.com&path=/home/nexusyl/nexusmk.nexussolutionsyl.com&deployment_mode=production&enabled=1"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $bodyString -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Result: $($r.Content)" -ForegroundColor Gray
    
    $data = $r.Content | ConvertFrom-Json
    if ($data.errors) {
        Write-Host "  ERROR: $($data.errors)" -ForegroundColor Red
    } elseif ($data.data -and $data.data[0].name) {
        Write-Host "  App registrada: $($data.data[0].name)" -ForegroundColor Green
    }
} catch {
    Write-Host "  Error: $_" -ForegroundColor Red
}

Start-Sleep -Seconds 3

# 4. Verificar la app registrada
Write-Host ""
Write-Host "[4] Verificando app registrada..." -ForegroundColor Yellow
try {
    $url = "$baseUrl/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    $data = $r.Content | ConvertFrom-Json
    if ($data.data) {
        foreach ($app in $data.data) {
            Write-Host "  App: $($app.name)" -ForegroundColor Cyan
            Write-Host "    domain: $($app.domain)" -ForegroundColor Gray
            Write-Host "    path: $($app.path)" -ForegroundColor Gray
            Write-Host "    enabled: $($app.enabled)" -ForegroundColor Gray
            Write-Host "    deployment_mode: $($app.deployment_mode)" -ForegroundColor Gray
            Write-Host "    ruby: $($app.ruby)" -ForegroundColor Gray
            Write-Host "    python: $($app.python)" -ForegroundColor Gray
            Write-Host "    nodejs: $($app.nodejs)" -ForegroundColor Gray
        }
    }
} catch {
    Write-Host "  Error: $_" -ForegroundColor Red
}

Write-Host ""

# 5. Probar la URL
Write-Host "[5] Probando https://nexusmk.nexussolutionsyl.com/..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/" -Method Get -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)" -ForegroundColor Green
    Write-Host "  Content-Length: $($r.Content.Length)" -ForegroundColor Gray
    if ($r.Content.Length -lt 500) {
        Write-Host "  Content: $($r.Content)" -ForegroundColor Gray
    }
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    if ($statusCode -eq 503) {
        Write-Host "  Status: 503 (Service Unavailable - Passenger starting?)" -ForegroundColor Yellow
    } elseif ($statusCode -eq 403) {
        Write-Host "  Status: 403 (Forbidden)" -ForegroundColor Red
    } elseif ($statusCode -eq 200) {
        Write-Host "  Status: 200 (OK)" -ForegroundColor Green
    } else {
        Write-Host "  Status: $statusCode" -ForegroundColor Red
    }
}

# 6. Probar API endpoint
Write-Host ""
Write-Host "[6] Probando API /api/health..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -Method Get -UseBasicParsing -TimeoutSec 15
    Write-Host "  Status: $($r.StatusCode)" -ForegroundColor Green
    Write-Host "  Content: $($r.Content)" -ForegroundColor Gray
} catch {
    $statusCode = $_.Exception.Response.StatusCode.value__
    Write-Host "  Status: $statusCode" -ForegroundColor Yellow
    if ($statusCode -eq 503) {
        Write-Host "  (503 means Passenger is trying but Node.js not configured)" -ForegroundColor Yellow
    } elseif ($statusCode -eq 502) {
        Write-Host "  (502 means Passenger started but app crashed)" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  Completado" -ForegroundColor Cyan
Write-Host "============================================"
