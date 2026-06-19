<?php
// Script rápido para configurar Passenger con Node.js
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

// 1. Encontrar Node.js
$nodePath = null;
$paths = [
    '/opt/alt/alt-nodejs20/root/usr/bin/node',
    '/opt/alt/alt-nodejs16/root/usr/bin/node',
    '/opt/alt/alt-nodejs19/root/usr/bin/node',
    '/opt/alt/alt-nodejs24/root/usr/bin/node',
];
foreach ($paths as $p) {
    if (file_exists($p)) { $nodePath = $p; break; }
}

if (!$nodePath) { die("NO NODE FOUND\n"); }
$ver = trim(exec($nodePath . ' --version 2>&1'));
echo "NODE: $nodePath ($ver)\n";

// 2. Crear wrapper node
file_put_contents($baseDir . '/node', "#!/bin/bash\nexec $nodePath \"\$@\"\n");
chmod($baseDir . '/node', 0755);
echo "NODE WRAPPER: OK\n";

// 3. Crear wrapper npm
$npmPath = dirname($nodePath) . '/npm';
if (file_exists($npmPath)) {
    file_put_contents($baseDir . '/npm', "#!/bin/bash\nexec $npmPath \"\$@\"\n");
    chmod($baseDir . '/npm', 0755);
    echo "NPM WRAPPER: OK\n";
}

// 4. Verificar passenger.js
if (!file_exists($baseDir . '/passenger.js')) {
    file_put_contents($baseDir . '/passenger.js', "require('dotenv').config({ path: __dirname + '/backend/.env' });\nconst app = require('./backend/app');\nmodule.exports = app;\n");
    echo "passenger.js: CREATED\n";
} else {
    echo "passenger.js: OK\n";
}

// 5. Verificar package.json main
$pkg = json_decode(file_get_contents($baseDir . '/package.json'), true);
if (($pkg['main'] ?? '') !== 'passenger.js') {
    $pkg['main'] = 'passenger.js';
    file_put_contents($baseDir . '/package.json', json_encode($pkg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "package.json main: UPDATED to passenger.js\n";
} else {
    echo "package.json main: OK (passenger.js)\n";
}

// 6. Crear .htaccess con PassengerEnabled
$htaccess = "PassengerEnabled On\n";
$htaccess .= "PassengerAppRoot $baseDir\n";
$htaccess .= "PassengerAppType node\n";
$htaccess .= "PassengerStartupFile passenger.js\n";
$htaccess .= "PassengerNodePath $nodePath\n";
$htaccess .= "PassengerFriendlyErrorPages On\n";
$htaccess .= "PassengerEnv NODE_ENV production\n";
$htaccess .= "PassengerEnv PORT 3000\n";
$htaccess .= "RewriteEngine On\n";
$htaccess .= "RewriteRule ^(.*)$ passenger.js [L]\n";
file_put_contents($baseDir . '/.htaccess', $htaccess);
echo ".htaccess: UPDATED\n";

// 7. Verificar node_modules
$nm = $baseDir . '/node_modules';
if (is_dir($nm)) {
    $count = count(scandir($nm)) - 2;
    echo "node_modules: $count modules\n";
    echo "express: " . (is_dir($nm . '/express') ? "OK" : "MISSING") . "\n";
    echo "dotenv: " . (is_dir($nm . '/dotenv') ? "OK" : "MISSING") . "\n";
} else {
    echo "node_modules: MISSING\n";
}

// 8. Probar passenger.js
$testCode = 'try { const app = require("./passenger.js"); console.log("LOAD: OK type=" + typeof app); } catch(e) { console.log("LOAD: ERROR " + e.message.substring(0,100)); }';
$cmd = 'cd ' . $baseDir . ' && ' . $nodePath . ' -e ' . escapeshellarg($testCode) . ' 2>&1';
exec($cmd, $out, $code);
echo implode("\n", $out) . "\n";

// 9. Verificar .env
foreach ([$baseDir . '/.env', $baseDir . '/backend/.env'] as $ep) {
    echo (file_exists($ep) ? "EXISTS" : "MISSING") . ": $ep\n";
}

echo "\nDONE\n";
