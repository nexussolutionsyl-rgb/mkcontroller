$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

# ============================================
# PASO 1: Subir archivos usando Fileman.savefile
# ============================================
Write-Host "=== Subiendo archivos al servidor ===" -ForegroundColor Green

# Función para subir un archivo
function Upload-File($remotePath, $localPath) {
    $content = [System.IO.File]::ReadAllText($localPath)
    $body = @{
        file = $remotePath
        content = $content
    } | ConvertTo-Json
    
    try {
        $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
        $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 30
        $result = $r.Content | ConvertFrom-Json
        if ($result.cpanelresult.event.result -eq 1) {
            Write-Host "  ✅ $remotePath" -ForegroundColor Green
            return $true
        } else {
            Write-Host "  ❌ $remotePath : $($result.cpanelresult.error)" -ForegroundColor Red
            return $false
        }
    } catch {
        Write-Host "  ❌ $remotePath : $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

# Subir start.js
Upload-File "/home/nexusyl/nexusmk.nexussolutionsyl.com/start.js" "C:\xampp2\htdocs\mk\start.js"

# Subir backend/app.js
Upload-File "/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/app.js" "C:\xampp2\htdocs\mk\backend\app.js"

# Subir backend/package.json
Upload-File "/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/package.json" "C:\xampp2\htdocs\mk\backend\package.json"

# Subir backend/config/config.js
Upload-File "/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/config/config.js" "C:\xampp2\htdocs\mk\backend\config\config.js"

# Subir backend/.env
Upload-File "/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/.env" "C:\xampp2\htdocs\mk\backend\.env.example"

Write-Host "`n=== Archivos subidos ===" -ForegroundColor Green
