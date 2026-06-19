$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "=== 1. Test Fileman.uploadfiles (API v2) ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    
    # Crear un archivo de prueba pequeño
    $testContent = "test file content"
    [System.IO.File]::WriteAllText("$PSScriptRoot\test_upload.txt", $testContent)
    
    $form = @{
        dir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'
        file = [System.IO.File]::ReadAllText("$PSScriptRoot\test_upload.txt")
        filename = 'test_upload.txt'
    }
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $form -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 2. Test Fileman.savefile (API v2 - GET method) ==="
try {
    $content = [System.Web.HttpUtility]::UrlEncode("console.log('test');")
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&path=/home/nexusyl/nexusmk.nexussolutionsyl.com/test_save.txt&content=$content&file=test_save.txt"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 3. Test Fileman.savefile (API v2 - POST with form) ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    
    $form = @{
        path = '/home/nexusyl/nexusmk.nexussolutionsyl.com/test_save2.txt'
        content = 'test content here'
        file = 'test_save2.txt'
    }
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $form -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 4. Test Fileman.get_file_content (API v2) ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=get_file_content&cpanel_jsonapi_apiversion=2&path=/home/nexusyl/nexusmk.nexussolutionsyl.com/start.js"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 5. Test UAPI Fileman/savefile ==="
try {
    $body = @{
        path = '/home/nexusyl/nexusmk.nexussolutionsyl.com/test_uapi.txt'
        content = 'test uapi content'
        file = 'test_uapi.txt'
    } | ConvertTo-Json
    
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/savefile"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 6. List files to verify ==="
try {
    $url = "https://server166.web-hosting.com:2083/execute/Fileman/listfiles?dir=/home/nexusyl/nexusmk.nexussolutionsyl.com"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
