$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Fix Passenger Config"
Write-Host "============================================"
Write-Host ""

# 1. Ver subdominio
Write-Host "[1] Verificando subdominio..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/SubDomain/listsubdomains"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        foreach ($sub in $result.data) {
            if ($sub.domain -like '*nexusmk*') {
                Write-Host "  Subdominio: $($sub.domain)"
                Write-Host "  Document Root: $($sub.documentroot)"
                Write-Host "  Root Domain: $($sub.rootdomain)"
            }
        }
    } else {
        Write-Host "  Error: $($result.errors)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 2. Ver app Passenger detallada
Write-Host ""
Write-Host "[2] App Passenger detallada..."
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1 -and $result.data.nexusmk) {
        $app = $result.data.nexusmk
        Write-Host "  Name: $($app.name)"
        Write-Host "  Enabled: $($app.enabled)"
        Write-Host "  Domain: $($app.domain)"
        Write-Host "  Path: $($app.path)"
        Write-Host "  Deployment: $($app.deployment_mode)"
        Write-Host "  Base URI: $($app.base_uri)"
        Write-Host "  Envvars: $($app.envvars)"
        Write-Host "  Deps npm: $($app.deps.npm)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 3. Probar con /api/health directamente
Write-Host ""
Write-Host "[3] Probando endpoints directamente..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -UseBasicParsing -TimeoutSec 15
    Write-Host "  /api/health: $($r.StatusCode) - $($r.Content)"
} catch {
    Write-Host "  /api/health: $($_.Exception.Message)"
}

try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/auth/login" -UseBasicParsing -TimeoutSec 15 -Method POST -Body '{"username":"admin","password":"admin123"}' -ContentType 'application/json'
    Write-Host "  /api/auth/login: $($r.StatusCode) - $($r.Content)"
} catch {
    Write-Host "  /api/auth/login: $($_.Exception.Message)"
}

# 4. Verificar si hay un .htaccess que redirija
Write-Host ""
Write-Host "[4] Verificando .htaccess..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir&showhidden=1"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    $result = $r.Content | ConvertFrom-Json
    foreach ($item in $result.cpanelresult.data) {
        if ($item.file -eq '.htaccess') {
            Write-Host "  .htaccess: ✅ presente ($($item.humansize))"
        }
        if ($item.file -eq '.htpasswd') {
            Write-Host "  .htpasswd: ✅ presente"
        }
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 5. Intentar editar app con base_uri correcto
Write-Host ""
Write-Host "[5] Reconfigurando app Passenger..."
try {
    $body = @{
        name = 'nexusmk'
        enabled = '1'
        deployment_mode = 'production'
        base_uri = '/'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    $result = $r.Content | ConvertFrom-Json
    if ($result.status -eq 1) {
        Write-Host "  ✅ App reconfigurada"
        Write-Host "  Enabled: $($result.data.enabled)"
        Write-Host "  Base URI: $($result.data.base_uri)"
    } else {
        Write-Host "  ❌ Error: $($result.errors)"
    }
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 6. Esperar y probar
Write-Host ""
Write-Host "[6] Esperando 20s para que Passenger recargue..."
Start-Sleep -Seconds 20

Write-Host ""
Write-Host "  Probando app..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com" -UseBasicParsing -TimeoutSec 15
    Write-Host "  Web: Status $($r.StatusCode)"
    if ($r.StatusCode -eq 200) {
        $content = $r.Content
        if ($content -match '<!DOCTYPE html>') {
            Write-Host "  ⚠️ Es HTML - puede ser Apache autoindex o la app SPA"
            if ($content -match 'autoindex') {
                Write-Host "  ❌ Es Apache autoindex - Passenger no esta manejando"
            } elseif ($content -match 'MkController') {
                Write-Host "  ✅ Es la app SPA de MkController!"
            } else {
                Write-Host "  Preview: $($content.Substring(0, [Math]::Min(300, $content.Length)))"
            }
        }
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
