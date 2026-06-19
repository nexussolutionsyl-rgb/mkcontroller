<?php
/**
 * Verifica el estado actual del servidor
 */
header('Content-Type: text/plain; charset=utf-8');

$appDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';

echo "=== VERIFICACION DE ESTADO ACTUAL ===\n\n";

// 1. Verificar .htaccess
echo "[1] .htaccess:\n";
$htaccess = @file_get_contents("$appDir/.htaccess");
if ($htaccess) {
    echo $htaccess . "\n";
} else {
    echo "  NO LEIDO\n\n";
}

// 2. Verificar passenger.js
echo "[2] passenger.js:\n";
$passenger = @file_get_contents("$appDir/passenger.js");
if ($passenger) {
    echo substr($passenger, 0, 300) . "\n...\n\n";
}

// 3. Verificar start.js
echo "[3] start.js:\n";
$start = @file_get_contents("$appDir/start.js");
if ($start) {
    echo substr($start, 0, 300) . "\n...\n\n";
}

// 4. Verificar procesos Node.js
echo "[4] Procesos Node.js:\n";
exec("ps aux | grep -E 'node|start\\.js|passenger' | grep -v grep 2>&1", $output);
foreach ($output as $line) {
    echo "  $line\n";
}
echo "\n";

// 5. Verificar puerto 3001
echo "[5] Puerto 3001:\n";
exec("lsof -i:3001 2>&1", $output);
foreach ($output as $line) {
    echo "  $line\n";
}
echo "\n";

// 6. Verificar si Passenger esta activo
echo "[6] Passenger:\n";
exec("ps aux | grep -i passenger | grep -v grep 2>&1", $output);
foreach ($output as $line) {
    echo "  $line\n";
}
if (empty($output)) echo "  No hay procesos Passenger visibles\n";
echo "\n";

// 7. Verificar node.pid
echo "[7] node.pid:\n";
$pidFile = "$appDir/node.pid";
if (file_exists($pidFile)) {
    echo "  PID: " . file_get_contents($pidFile) . "\n";
} else {
    echo "  No existe\n";
}
echo "\n";

// 8. Test local health
echo "[8] Test local health (127.0.0.1:3001):\n";
$ch = curl_init("http://127.0.0.1:3001/api/health");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3]);
$r = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "  HTTP: $code\n";
echo "  Response: " . ($r ?: $err) . "\n\n";

// 9. Test nexusmk health local
echo "[9] Test local nexusmk/health (127.0.0.1:3001):\n";
$ch = curl_init("http://127.0.0.1:3001/api/nexusmk/health");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5]);
$r = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "  HTTP: $code\n";
echo "  Response: " . ($r ?: $err) . "\n\n";

echo "=== FIN ===\n";
