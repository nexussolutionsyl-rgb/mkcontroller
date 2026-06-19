$cred = 'nexusyl:n0A3$oDTToa4%Z7'
$bytes = [System.Text.Encoding]::ASCII.GetBytes($cred)
$base64 = [System.Convert]::ToBase64String($bytes)
$headers = @{Authorization='Basic '+$base64}
$remoteDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com'

Write-Host "============================================"
Write-Host "  MkController - Test Node.js"
Write-Host "============================================"
Write-Host ""

# 1. Crear un script PHP que pruebe Node.js directamente
Write-Host "[1] Creando script de prueba Node.js..."
$phpScript = @'
<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== Test Node.js ===\n\n";

// 1. Check node version
echo "1. Node version:\n";
exec('node --version 2>&1', $output, $code);
echo "   " . implode("\n   ", $output) . "\n";
echo "   Exit code: $code\n\n";

// 2. Check npm version
echo "2. NPM version:\n";
$output = [];
exec('npm --version 2>&1', $output, $code);
echo "   " . implode("\n   ", $output) . "\n";
echo "   Exit code: $code\n\n";

// 3. Try to require express from passenger.js context
echo "3. Testing require('express') from root:\n";
$output = [];
exec('cd ' . $baseDir . ' && node -e "try { const e = require(\"express\"); console.log(\"express: OK version=\" + e.version); } catch(e) { console.log(\"express ERROR: \" + e.message); }" 2>&1', $output, $code);
echo "   " . implode("\n   ", $output) . "\n\n";

// 4. Test require all deps
echo "4. Testing all dependencies:\n";
$deps = ['express', 'cors', 'helmet', 'dotenv', 'jsonwebtoken', 'bcryptjs', 'express-rate-limit', 'uuid', 'mysql2', 'node-routeros', 'ws'];
foreach ($deps as $dep) {
    $output = [];
    exec('cd ' . $baseDir . ' && node -e "try { require(\"' . $dep . '\"); console.log(\"' . $dep . ': OK\"); } catch(e) { console.log(\"' . $dep . ': ERROR - \" + e.message); }" 2>&1', $output, $code);
    echo "   " . implode("\n   ", $output) . "\n";
}
echo "\n";

// 5. Test loading passenger.js
echo "5. Testing passenger.js load:\n";
$output = [];
exec('cd ' . $baseDir . ' && node -e "try { const app = require(\"./passenger.js\"); console.log(\"passenger.js: OK - app type=\" + typeof app); } catch(e) { console.log(\"passenger.js ERROR: \" + e.message + \"\\n\" + e.stack); }" 2>&1', $output, $code);
echo "   " . implode("\n   ", $output) . "\n\n";

// 6. Check if .env is readable
echo "6. Checking .env files:\n";
$files = [
    $baseDir . '/.env',
    $baseDir . '/backend/.env',
    $baseDir . '/passenger.js',
    $baseDir . '/package.json',
    $baseDir . '/start.js'
];
foreach ($file as $f) {
    if (file_exists($f)) {
        echo "   " . basename($f) . ": OK (" . filesize($f) . " bytes)\n";
    } else {
        echo "   " . basename($f) . ": NOT FOUND\n";
    }
}
echo "\n";

// 7. Check passenger error log
echo "7. Checking Passenger error logs:\n";
$logDirs = [
    $baseDir . '/../logs',
    $baseDir . '/log',
    $baseDir . '/tmp',
    '/home/nexusyl/logs',
    '/home/nexusyl/.passenger',
    '/opt/cpanel/ea-ruby27/root/usr/share/passenger',
];
foreach ($logDirs as $dir) {
    if (is_dir($dir)) {
        echo "   $dir: EXISTS\n";
        $files2 = scandir($dir);
        foreach ($files2 as $f) {
            if ($f != '.' && $f != '..') {
                echo "     - $f\n";
            }
        }
    }
}

echo "\n=== Done ===\n";
?>
'@

$phpScriptPath = "C:\xampp2\htdocs\mk\test_node.php"
[System.IO.File]::WriteAllText($phpScriptPath, $phpScript, [System.Text.Encoding]::UTF8)

Write-Host "  Script created: test_node.php"

# 2. Upload test_node.php
Write-Host ""
Write-Host "[2] Uploading test_node.php..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=uploadfiles&cpanel_jsonapi_apiversion=2"
    $filePath = "C:\xampp2\htdocs\mk\test_node.php"
    $boundary = [System.Guid]::NewGuid().ToString()
    $fileContent = [System.IO.File]::ReadAllBytes($filePath)
    $fileName = "test_node.php"
    
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

# 3. Execute test_node.php
Write-Host ""
Write-Host "[3] Executing test_node.php..."
try {
    $r = Invoke-WebRequest -Uri "https://nexusmk.nexussolutionsyl.com/test_node.php" -UseBasicParsing -TimeoutSec 30
    Write-Host $r.Content
} catch {
    Write-Host "  ERROR: $($_.Exception.Message)"
}

# 4. Cleanup
Write-Host ""
Write-Host "[4] Cleaning up..."
try {
    $url = "https://server166.web-hosting.com:2083/json-api/cpanel?cpanel_jsonapi_module=Fileman&cpanel_jsonapi_func=fileop&cpanel_jsonapi_apiversion=2"
    $body = "op=trash&source-files[]=test_node.php&dir=$remoteDir"
    $r = Invoke-WebRequest -Uri $url -Headers $headers -Method POST -Body $body -ContentType 'application/x-www-form-urlencoded' -UseBasicParsing -TimeoutSec 15
    Write-Host "  Cleanup: $($r.Content)"
} catch {
    Write-Host "  Cleanup ERROR: $($_.Exception.Message)"
}

Write-Host ""
Write-Host "============================================"
