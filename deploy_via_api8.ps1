$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# Configurar variables de entorno para la app Node.js
Write-Host "=== Configurando variables de entorno ==="

# Primero, veamos si podemos editar la aplicación para agregar env vars
try {
    $body = @{
        name = 'nexusmk'
        envvars = @{
            NODE_ENV = 'production'
            PORT = '3000'
            JWT_SECRET = 'mk3controller_secret_key_2024_nexus'
            CORS_ORIGIN = 'https://nexusmk.nexussolutionsyl.com'
        }
    } | ConvertTo-Json -Depth 5
    
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/edit_application"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

# Ver la configuración actual
Write-Host "`n=== Config actual ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/PassengerApps/list_applications"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
