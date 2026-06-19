<?php
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== Checking node_modules locations ===\n\n";

// Check root node_modules
$rootNm = $baseDir . '/node_modules';
if (is_dir($rootNm)) {
    $dirs = scandir($rootNm);
    $modules = array_diff($dirs, ['.', '..']);
    echo "Root node_modules (" . count($modules) . " modules):\n";
    foreach (array_slice($modules, 0, 20) as $m) {
        echo "  - $m\n";
    }
    if (count($modules) > 20) {
        echo "  ... and " . (count($modules) - 20) . " more\n";
    }
} else {
    echo "Root node_modules: NOT FOUND\n";
}

echo "\n";

// Check backend node_modules
$backendNm = $baseDir . '/backend/node_modules';
if (is_dir($backendNm)) {
    $dirs = scandir($backendNm);
    $modules = array_diff($dirs, ['.', '..']);
    echo "Backend node_modules (" . count($modules) . " modules):\n";
    foreach (array_slice($modules, 0, 20) as $m) {
        echo "  - $m\n";
    }
    if (count($modules) > 20) {
        echo "  ... and " . (count($modules) - 20) . " more\n";
    }
    
    // Check specifically for express
    if (in_array('express', $modules)) {
        echo "\n  ✅ express FOUND in backend/node_modules/\n";
    } else {
        echo "\n  ❌ express NOT in backend/node_modules/\n";
    }
    
    // Check for cors
    if (in_array('cors', $modules)) {
        echo "  ✅ cors FOUND\n";
    } else {
        echo "  ❌ cors NOT found\n";
    }
    
    // Check for dotenv
    if (in_array('dotenv', $modules)) {
        echo "  ✅ dotenv FOUND\n";
    } else {
        echo "  ❌ dotenv NOT found\n";
    }
    
    // Check for helmet
    if (in_array('helmet', $modules)) {
        echo "  ✅ helmet FOUND\n";
    } else {
        echo "  ❌ helmet NOT found\n";
    }
    
    // Check for jsonwebtoken
    if (in_array('jsonwebtoken', $modules)) {
        echo "  ✅ jsonwebtoken FOUND\n";
    } else {
        echo "  ❌ jsonwebtoken NOT found\n";
    }
    
    // Check for mysql2
    if (in_array('mysql2', $modules)) {
        echo "  ✅ mysql2 FOUND\n";
    } else {
        echo "  ❌ mysql2 NOT found\n";
    }
    
    // Check for express-rate-limit
    if (in_array('express-rate-limit', $modules)) {
        echo "  ✅ express-rate-limit FOUND\n";
    } else {
        echo "  ❌ express-rate-limit NOT found\n";
    }
} else {
    echo "Backend node_modules: NOT FOUND\n";
}

echo "\n=== Checking .env files ===\n";
$rootEnv = $baseDir . '/.env';
$backendEnv = $baseDir . '/backend/.env';

if (file_exists($rootEnv)) {
    echo "Root .env: ✅ (" . filesize($rootEnv) . " bytes)\n";
} else {
    echo "Root .env: ❌ NOT FOUND\n";
}

if (file_exists($backendEnv)) {
    echo "Backend .env: ✅ (" . filesize($backendEnv) . " bytes)\n";
} else {
    echo "Backend .env: ❌ NOT FOUND\n";
}

echo "\n=== Checking passenger.js ===\n";
$passengerJs = $baseDir . '/passenger.js';
if (file_exists($passengerJs)) {
    echo "passenger.js: ✅ (" . filesize($passengerJs) . " bytes)\n";
    echo "Content: " . file_get_contents($passengerJs) . "\n";
} else {
    echo "passenger.js: ❌ NOT FOUND\n";
}

echo "\n=== Checking package.json ===\n";
$pkgJson = $baseDir . '/package.json';
if (file_exists($pkgJson)) {
    echo "package.json: ✅ (" . filesize($pkgJson) . " bytes)\n";
    $pkg = json_decode(file_get_contents($pkgJson), true);
    echo "main: " . ($pkg['main'] ?? 'NOT SET') . "\n";
} else {
    echo "package.json: ❌ NOT FOUND\n";
}
?>
