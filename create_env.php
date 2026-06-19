<?php
$envContent = 'NODE_ENV=production
PORT=3000
JWT_SECRET=mkcontroller_superadmin_jwt_secret_key_2024_production
JWT_EXPIRES_IN=24h
CORS_ORIGIN=https://nexusmk.nexussolutionsyl.com
DB_PATH=./backend/data
';

$filePath = '/home/nexusyl/nexusmk.nexussolutionsyl.com/.env';
file_put_contents($filePath, $envContent);
echo "SUCCESS: .env created at $filePath\n";

// Also create backend/.env
$backendEnvPath = '/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/.env';
file_put_contents($backendEnvPath, $envContent);
echo "SUCCESS: .env created at $backendEnvPath\n";

// Read passenger.js to verify
$passengerPath = '/home/nexusyl/nexusmk.nexussolutionsyl.com/passenger.js';
if (file_exists($passengerPath)) {
    echo "passenger.js content:\n";
    echo file_get_contents($passengerPath);
} else {
    echo "ERROR: passenger.js not found\n";
}

// Read package.json
$pkgPath = '/home/nexusyl/nexusmk.nexussolutionsyl.com/package.json';
if (file_exists($pkgPath)) {
    echo "\npackage.json content:\n";
    echo file_get_contents($pkgPath);
} else {
    echo "\nERROR: package.json not found\n";
}

// Check if express is accessible
echo "\n\nChecking express module...\n";
if (file_exists('/home/nexusyl/nexusmk.nexussolutionsyl.com/backend/node_modules/express')) {
    echo "express found in backend/node_modules/\n";
} else {
    echo "express NOT found in backend/node_modules/\n";
}
?>
