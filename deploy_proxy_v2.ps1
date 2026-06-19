#Requires -Version 5.1
param()

$ErrorActionPreference = "Stop"

$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

function Upload-File {
    param($LocalFile, $RemoteName)
    
    # Delete old
    try {
        $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
        $body = "op=trash&sourcefiles=$remoteDir/$RemoteName"
        Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
    } catch {}
    
    Start-Sleep -Seconds 1
    
    $fileBytes = [System.IO.File]::ReadAllBytes((Resolve-Path $LocalFile))
    $fileContent = [System.Text.Encoding]::Default.GetString($fileBytes)
    
    $boundary = [Guid]::NewGuid().ToString("N")
    $lf = "`r`n"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"$RemoteName`""
    $bodyLines += "Content-Type: application/octet-stream"
    $bodyLines += ""
    $bodyLines += $fileContent
    $bodyLines += "--$boundary--"
    
    $bodyString = $bodyLines -join $lf
    
    $uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
    
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
    Write-Host "Uploaded $RemoteName"
}

Write-Host "=== Deploy Proxy v2 ===" -ForegroundColor Cyan

# Step 1: Upload updated start.js
Write-Host "`nStep 1: Upload start.js" -ForegroundColor Yellow
Upload-File -LocalFile "start.js" -RemoteName "start.js"

# Step 2: Upload updated proxy.php
Write-Host "`nStep 2: Upload proxy.php" -ForegroundColor Yellow
Upload-File -LocalFile "proxy.php" -RemoteName "proxy.php"

Start-Sleep -Seconds 2

# Step 3: Kill any existing node process and start fresh
Write-Host "`nStep 3: Start Node.js server via PHP" -ForegroundColor Yellow

# Create a PHP script to start Node.js
$phpStartScript = @'
<?php
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$appDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$entryPoint = $appDir . '/start.js';
$pidFile = $appDir . '/node.pid';
$logFile = $appDir . '/node.log';

// Kill existing node processes
exec("pkill -f \"$entryPoint\" 2>/dev/null", $output, $code);
echo "Kill existing: exit=$code\n";

// Remove old PID file
if (file_exists($pidFile)) unlink($pidFile);

// Start Node.js
$cmd = "cd $appDir && PORT=3001 nohup $nodeBin $entryPoint > $logFile 2>&1 & echo $!";
exec($cmd, $output, $exitCode);
echo "Start cmd: $cmd\n";
echo "Exit code: $exitCode\n";
echo "Output: " . implode("\n", $output) . "\n";

if ($exitCode === 0 && !empty($output)) {
    $pid = trim($output[0]);
    file_put_contents($pidFile, $pid);
    echo "PID: $pid\n";
    
    // Wait and test
    sleep(3);
    
    // Test connection
    $ch = curl_init('http://127.0.0.1:3001/api/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "Health check: HTTP $httpCode\n";
    echo "Response: " . ($result ? substr($result, 0, 500) : "(empty)") . "\n";
    if ($error) echo "CURL Error: $error\n";
    
    // Show log
    if (file_exists($logFile)) {
        echo "\nNode log:\n";
        echo file_get_contents($logFile);
    }
} else {
    echo "FAILED to start Node.js\n";
}
'@

# Upload and execute the start script
$startScriptPath = "$remoteDir/_start_node.php"
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&sourcefiles=$startScriptPath"
    Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15 | Out-Null
} catch {}

Start-Sleep -Seconds 1

$boundary = [Guid]::NewGuid().ToString("N")
$lf = "`r`n"
$bodyLines = @()
$bodyLines += "--$boundary"
$bodyLines += "Content-Disposition: form-data; name=`"file-0`"; filename=`"_start_node.php`""
$bodyLines += "Content-Type: application/x-php"
$bodyLines += ""
$bodyLines += $phpStartScript
$bodyLines += "--$boundary--"
$bodyString = $bodyLines -join $lf
$uploadUrl = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2&dir=$remoteDir"
try {
    $r = Invoke-WebRequest -Uri $uploadUrl -Headers $headers -Method POST -Body $bodyString -ContentType "multipart/form-data; boundary=$boundary" -UseBasicParsing -TimeoutSec 30
} catch {}

Start-Sleep -Seconds 2

Write-Host "`nExecuting _start_node.php..." -ForegroundColor Yellow
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/_start_node.php" -Method Get -UseBasicParsing -TimeoutSec 30
    Write-Host $r.Content
} catch {
    Write-Host "Error: $_"
    try {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $body = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "Body: $body"
    } catch {}
}

Start-Sleep -Seconds 2

# Step 4: Test API endpoints
Write-Host "`n=== Test API Endpoints ===" -ForegroundColor Cyan

# Test /api/health
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/api/health" -Method Get -UseBasicParsing -TimeoutSec 15
    Write-Host "/api/health -> Status: $($r.StatusCode)"
    Write-Host "Content-Type: $($r.Headers['Content-Type'])"
    Write-Host "Content: $($r.Content.Substring(0, [Math]::Min(500, $r.Content.Length)))"
} catch {
    $code = $_.Exception.Response.StatusCode.value__
    Write-Host "/api/health -> Status: $code"
    try {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $body = $reader.ReadToEnd()
        $reader.Close()
        Write-Host "Body: $body"
    } catch {}
}

Write-Host ""
Write-Host "=== Done ===" -ForegroundColor Green
