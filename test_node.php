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