#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "=== Upload diagnose_api.php ===" -ForegroundColor Cyan

# Delete existing
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$remoteDir/diagnose_api.php"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
    Write-Host "Deleted old file"
} catch {
    Write-Host "No old file to delete"
}

Start-Sleep -Seconds 1

# Upload
try {
    $localFile = "diagnose_api.php"
    $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $localFile))
    $fileContent = [System.Text.Encoding]::Default.GetString($fileBytes)
    
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"diagnose_api.php`""
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += $fileContent
    $bodyLines += "--$boundary--"
    
    $bodyString = $bodyLines -join $lf
    
    $uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
    Write-Host "Upload response: $($r.Content)"
} catch {
    Write-Host "Upload error: $_"
}

Start-Sleep -Seconds 2

Write-Host ""
Write-Host "=== Execute diagnose_api.php ===" -ForegroundColor Cyan

# Execute via HTTP
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/diagnose_api.php" -Method Get -UseBasicParsing -TimeoutSec 30
    Write-Host "Status: $($r.StatusCode)"
    Write-Host "Content:"
    Write-Host $r.Content
} catch {
    Write-Host "HTTP Error: $_"
    try {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $body = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "Body: $body"
    } catch {}
}

Write-Host ""
Write-Host "=== Done ===" -ForegroundColor Green
