$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Fix Node.js Path"
Write-Host "============================================"
Write-Host ""

# Crear PHP script para encontrar y configurar Node.js
$phpScript = @'
<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== Finding Node.js ===\n\n";

// 1. Find node binary
$possiblePaths = [
    '/opt/cpanel/ea-ruby27/root/usr/share/passenger/node/bin/node',
    '/opt/cpanel/ea-ruby27/root/usr/share/passenger/node/node',
    '/usr/local/bin/node',
    '/usr/bin/node',
    '/usr/local/cpanel/3rdparty/bin/node',
];

$nodePath = null;
foreach ($possiblePaths as $p) {
    if (file_exists($p)) {
        $nodePath = $p;
        echo "Node found: $p\n";
        break;
    }
}

if (!$nodePath) {
    // Search for node
    exec('find /opt -name "node" -type f 2>/dev/null | head -5', $output);
    if (!empty($output)) {
        echo "Found node binaries:\n";
        foreach ($output as $o) {
            echo "  $o\n";
            if (!$nodePath) $nodePath = $o;
        }
    }
}

if ($nodePath) {
    echo "\nUsing node: $nodePath\n";
    exec($nodePath . ' --version 2>&1', $output);
    echo "Version: " . implode("\n", $output) . "\n";
    
    // 2. Create a wrapper script for node
    $wrapperContent = "#!/bin/bash\n$nodePath \"\$@\"\n";
    $wrapperPath = $baseDir . '/node';
    file_put_contents($wrapperPath, $wrapperContent);
    chmod($wrapperPath, 0755);
    echo "\nWrapper created: $wrapperPath\n";
    
    // 3. Create a wrapper for npm
    $npmPath = dirname($nodePath) . '/npm';
    if (file_exists($npmPath)) {
        $npmWrapper = "#!/bin/bash\n$npmPath \"\$@\"\n";
        $npmWrapperPath = $baseDir . '/npm';
        file_put_contents($npmWrapperPath, $npmWrapper);
        chmod($npmWrapperPath, 0755);
        echo "NPM wrapper created: $npmWrapperPath\n";
    }
    
    // 4. Test require express
    echo "\n=== Testing require('express') ===\n";
    $testCode = 'try { const e = require("express"); console.log("express: OK version=" + e.version); } catch(e) { console.log("express ERROR: " + e.message); }';
    $cmd = 'cd ' . $baseDir . ' && ' . $nodePath . ' -e ' . escapeshellarg($testCode) . ' 2>&1';
    exec($cmd, $output, $code);
    echo implode("\n", $output) . "\n";
    
    // 5. Test loading passenger.js
    echo "\n=== Testing passenger.js ===\n";
    $cmd = 'cd ' . $baseDir . ' && ' . $nodePath . ' -e "try { const app = require(\"./passenger.js\"); console.log(\"passenger.js: OK - app type=\" + typeof app); } catch(e) { console.log(\"passenger.js ERROR: \" + e.message.substring(0,200)); }" 2>&1';
    exec($cmd, $output, $code);
    echo implode("\n", $output) . "\n";
    
    // 6. Test starting the app briefly
    echo "\n=== Testing app startup (3s timeout) ===\n";
    $cmd = 'cd ' . $baseDir . ' && timeout 3 ' . $nodePath . ' -e "const app = require(\"./passenger.js\"); console.log(\"App loaded, type=\" + typeof app); const server = app.listen(3001, () => { console.log(\"Server started on 3001\"); server.close(); });" 2>&1';
    exec($cmd, $output, $code);
    echo implode("\n", $output) . "\n";
    
} else {
    echo "Node.js NOT FOUND on system!\n";
    echo "Checking /opt/cpanel/...\n";
    exec('ls -la /opt/cpanel/ea-ruby27/root/usr/share/passenger/node/ 2>&1', $output);
    echo implode("\n", $output) . "\n";
}

echo "\n=== Done ===\n";
?>
'@

$phpScriptPath = "C:\xampp2\htdocs\mk\fix_node.php"
[System.IO.File]::WriteAllText($phpScriptPath, $phpScript, [System.Text.Encoding]::UTF8)

Write-Host "[1] Uploading fix_node.php..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    $filePath = "C:\xampp2\htdocs\mk\fix_node.php"
    $boundary = [System.Guid]::NewGuid().ToString()
    $fileContent = [System.IO.File]::ReadAllBytes($filePath)
    $fileName = "fix_node.php"
    
    $bodyLines = @()
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="file-0"; filename="' + $fileName + '"'
    $bodyLines += "Content-Type: application/x-php"
    $bodyLines += ""
    $bodyLines += [System.Text.Encoding]::UTF8.GetString($fileContent)
    $bodyLines += "--$boundary"
    $bodyLines += 'Content-Disposition: form-data; name="dir"'
    $bodyLines += ""
    $bodyLines += $remoteDir
    $bodyLines += "--$boundary--"
    
    $bodyStr = $bodyLines -join "`r`n"
    $bodyBytes = [System.Text.Encoding]::UTF8.GetBytes($bodyStr)
    $contentType = "multipart/form-data; boundary=$boundary"
    
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $bodyBytes -ContentType $contentType -UseBasicParsing -TimeoutSec 30
    Write-Host "  Upload: $($r.Content)"
} catch {
    Write-Host "  Upload ERROR: $($_.Exception.Message)"
}

Start-Sleep -Seconds 2

Write-Host ""
Write-Host "[2] Executing fix_node.php..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/fix_node.php" -UseBasicParsing -TimeoutSec 30
    Write-Host $r.Content
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "[3] Cleaning up..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&source-files[]=fix_node.php&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Cleanup: $($r.Content)"
} catch {
    Write-Host "  Cleanup ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
