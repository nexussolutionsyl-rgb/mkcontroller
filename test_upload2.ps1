$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}

Write-Host "=== 1. Fileman listfiles (API v2) ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=listfiles&cpanel_jsonapi_apiversion=2&dir=/home/nexusyl/nexusmk.nexussolutionsyl.com"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host $r.Content
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 2. Fileman uploadfiles (API v2) with multipart ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=/home/nexusyl/nexusmk.nexussolutionsyl.com"
    
    # Create test file
    $testFilePath = "$PSScriptRoot\test_upload.txt"
    "console.log('test upload ok');" | Out-File -FilePath $testFilePath -Encoding ASCII
    
    # Use multipart/form-data
    $boundary = [Guid]::NewGuid().ToString()
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"dir`"$lf"
    $bodyLines += "/home/nexusyl/nexusmk.nexussolutionsyl.com"
    
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"filename`"$lf"
    $bodyLines += "test_upload.txt"
    
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file`"; filename=`"test_upload.txt`""
    $bodyLines += "Content-Type: text/plain$lf"
    $bodyLines += "console.log('test upload ok');"
    
    $bodyLines += "--$boundary--"
    
    $body = [string]::Join($lf, $bodyLines)
    
    $multipartHeaders = $headers.Clone()
    $multipartHeaders['Content-Type'] = "multipart/form-data; boundary=$boundary"
    
    $r = Invoke-WebRequest -Uri $url -Headers $multipartHeaders -Method POST -Body $body -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 3. Fileman savefile (API v2) - probar con encoding correcto ==="
try {
    Add-Type -AssemblyName System.Web
    
    $content = [System.Web.HttpUtility]::UrlEncode("console.log('test savefile');", [System.Text.Encoding]::UTF8)
    $path = [System.Web.HttpUtility]::UrlEncode("/home/nexusyl/nexusmk.nexussolutionsyl.com/test_save.txt", [System.Text.Encoding]::UTF8)
    
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&path=$path&content=$content&file=test_save.txt"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 4. Fileman savefile (API v2) - POST with JSON body ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    
    $body = @{
        path = '/home/nexusyl/nexusmk.nexussolutionsyl.com/test_save3.txt'
        content = 'test content here 3'
        file = 'test_save3.txt'
    } | ConvertTo-Json
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/json' -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 5. Fileman savefile (API v2) - POST with x-www-form-urlencoded ==="
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2"
    
    $formBody = "path=/home/nexusyl/nexusmk.nexussolutionsyl.com/test_save4.txt&content=test+content+4&file=test_save4.txt"
    
    $formHeaders = $headers.Clone()
    $formHeaders['Content-Type'] = 'application/x-www-form-urlencoded'
    
    $r = Invoke-WebRequest -Uri $url -Headers $formHeaders -Method POST -Body $formBody -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }

Write-Host "`n=== 6. Fileman savefile (API v2) - GET with all params in URL ==="
try {
    Add-Type -AssemblyName System.Web
    
    $content = [System.Web.HttpUtility]::UrlEncode("test content 5")
    $path = [System.Web.HttpUtility]::UrlEncode("/home/nexusyl/nexusmk.nexussolutionsyl.com/test_save5.txt")
    
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=savefile&cpanel_jsonapi_apiversion=2&dir=/home/nexusyl/nexusmk.nexussolutionsyl.com&path=$path&content=$content&file=test_save5.txt"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method GET -UseBasicParsing -TimeoutSec 15
    Write-Host "Response: $($r.Content)"
} catch { Write-Host "ERROR: $($_.Exception.Message)" }
