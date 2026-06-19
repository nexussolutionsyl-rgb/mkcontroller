<?php
/**
 * Fuerza el reinicio completo de Node.js y verifica la configuracion
 */
header('Content-Type: text/plain; charset=utf-8');

$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$appDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$entryPoint = $appDir . '/start.js';
$nodePort = 3001;
$controllerFile = $appDir . '/backend/controllers/nexusmkController.js';

echo "=== DIAGNOSTICO Y REINICIO DE NODE.JS ===\n\n";

// PASO 1: Verificar el contenido del controlador
echo "[PASO 1] Leyendo DB_CONFIG del controlador...\n";
$content = file_get_contents($controllerFile);
$pattern = '/const DB_CONFIG\s*=\s*\{[^}]+\}/s';
if (preg_match($pattern, $content, $matches)) {
    echo "  DB_CONFIG actual:\n";
    echo "  " . str_replace("\n", "\n  ", $matches[0]) . "\n\n";
} else {
    echo "  ERROR: No se pudo leer DB_CONFIG\n\n";
}

// PASO 2: Verificar procesos Node.js
echo "[PASO 2] Verificando procesos Node.js...\n";
exec("ps aux | grep -i node 2>&1", $output);
foreach ($output as $line) {
    if (strpos($line, 'start.js') !== false || strpos($line, 'node') !== false) {
        echo "  $line\n";
    }
}
echo "\n";

// PASO 3: Verificar puerto 3001
echo "[PASO 3] Verificando puerto 3001...\n";
exec("lsof -i:3001 2>&1", $output);
foreach ($output as $line) {
    echo "  $line\n";
}
echo "\n";

// PASO 4: Matar TODOS los procesos Node.js
echo "[PASO 4] Matando todos los procesos Node.js...\n";
exec("pkill -9 -f \"node.*start.js\" 2>&1", $output);
exec("kill -9 \$(lsof -t -i:{$nodePort} 2>/dev/null) 2>&1", $output);
exec("pkill -9 -f \"node.*$appDir\" 2>&1", $output);
echo "  OK: Procesos eliminados\n\n";

// PASO 5: Esperar
sleep(2);

// PASO 6: Iniciar Node.js FRESCO
echo "[PASO 5] Iniciando Node.js FRESCO en puerto {$nodePort}...\n";
$cmd = "cd $appDir && PORT=$nodePort nohup $nodeBin $entryPoint > /dev/null 2>&1 & echo $!";
$output = [];
exec($cmd, $output, $exitCode);
$pid = trim($output[0] ?? '');
echo "  PID: {$pid}\n";
echo "  Exit code: {$exitCode}\n\n";

// PASO 7: Esperar a que inicie
echo "[PASO 6] Esperando 5 segundos...\n";
sleep(5);

// PASO 8: Verificar health
echo "[PASO 7] Verificando /api/health...\n";
$ch = curl_init("http://127.0.0.1:{$nodePort}/api/health");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);
echo "  HTTP: {$httpCode}\n";
echo "  Response: " . ($response ?: $curlError) . "\n\n";

// PASO 9: Verificar nexusmk/health
echo "[PASO 8] Verificando /api/nexusmk/health...\n";
$ch = curl_init("http://127.0.0.1:{$nodePort}/api/nexusmk/health");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);
echo "  HTTP: {$httpCode}\n";
echo "  Response: " . ($response ?: $curlError) . "\n\n";

echo "=== COMPLETADO ===\n";
