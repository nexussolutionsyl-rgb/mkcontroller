$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "=== 1. Verificar archivo subido ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=/home/nexusyl/nexusmk.nexussolutionsyl.com"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 2. Buscar API para ejecutar comandos ==="
try {
    # Probar si existe execute command
    $url = "https://server166.web-hosting.com:2083/execute/Execute/command"
    $body = @{command = 'ls -la'} | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
    Write-Host "Execute/command: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 3. Buscar API de Fileman/upload_files (UAPI) ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/upload_files"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "Fileman/upload_files: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 4. Buscar API de Fileman/get_file_content (UAPI) ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/get_file_content?path=/home/nexusyl/nexusmk.nexussolutionsyl.com/test_upload.txt"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "Fileman/get_file_content: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 5. Buscar API de Fileman (list all functions) ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/list_files?dir=/home/nexusyl/nexusmk.nexussolutionsyl.com"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "Fileman/list_files: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 6. Probar Fileman/upload_files con POST ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/upload_files"
    $body = @{
        dir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'
    } | ConvertTo-Json
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 10
    Write-Host "Fileman/upload_files POST: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 7. Probar VersionControl (si existe) ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/VersionControlDeployment/list_deployments"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "VersionControl: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
