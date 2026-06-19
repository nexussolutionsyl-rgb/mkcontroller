<?php
/**
 * Fuerza el reinicio completo del servidor Node.js
 * Mata todos los procesos node, elimina el cache, y reinicia
 */
header('Content-Type: text/plain; charset=utf-8');

$appDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodeBin = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$entryPoint = $appDir . '/start.js';
$pidFile = $appDir . '/node.pid';
$logFile = $appDir . '/node.log';
$nodePort = 3001;

echo "=== REINICIO COMPLETO DEL SERVIDOR NODE.JS ===\n\n";

// Paso 1: Matar TODOS los procesos node
echo "[1] Matando procesos Node.js existentes...\n";
exec("pkill -9 -f 'node.*start\\.js' 2>&1", $output, $code);
echo "  pkill start.js: " . implode("\n  ", $output) . "\n";

exec("pkill -9 -f 'node.*backend/app' 2>&1", $output, $code);
echo "  pkill backend/app: " . implode("\n  ", $output) . "\n";

exec("pkill -9 node 2>&1", $output, $code);
echo "  pkill node: " . implode("\n  ", $output) . "\n";

// Esperar a que los procesos terminen
sleep(2);

// Verificar que no queden procesos
exec("ps aux | grep node | grep -v grep 2>&1", $output);
echo "  Procesos restantes: " . (empty($output) ? "NINGUNO" : implode("\n  ", $output)) . "\n\n";

// Paso 2: Eliminar el archivo PID
echo "[2] Eliminando node.pid...\n";
if (file_exists($pidFile)) {
    unlink($pidFile);
    echo "  Eliminado\n";
} else {
    echo "  No existe\n";
}
echo "\n";

// Paso 3: Verificar que el puerto 3001 esté libre
echo "[3] Verificando puerto 3001...\n";
$ch = @curl_init("http://127.0.0.1:$nodePort/api/health");
@curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
@curl_setopt($ch, CURLOPT_TIMEOUT, 2);
@curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
$r = @curl_exec($ch);
$httpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
@curl_close($ch);
echo "  Puerto 3001 responde: " . ($httpCode ? "HTTP $httpCode" : "LIBRE") . "\n\n";

// Paso 4: Verificar el archivo nexusmkController.js
echo "[4] Verificando DB_CONFIG en nexusmkController.js...\n";
$controller = file_get_contents("$appDir/backend/controllers/nexusmkController.js");
if (preg_match('/user:\s*\'(.*?)\'/', $controller, $m)) {
    echo "  User: " . $m[1] . "\n";
}
if (preg_match('/password:\s*\'(.*?)\'/', $controller, $m)) {
    echo "  Password: " . $m[1] . "\n";
}
if (preg_match('/database:\s*\'(.*?)\'/', $controller, $m)) {
    echo "  Database: " . $m[1] . "\n";
}
echo "\n";

// Paso 5: Iniciar Node.js
echo "[5] Iniciando servidor Node.js...\n";
$cmd = "cd $appDir && PORT=$nodePort nohup $nodeBin $entryPoint > $logFile 2>&1 & echo $!";
exec($cmd, $output, $exitCode);
$pid = trim($output[0] ?? '');
echo "  PID: " . ($pid ?: "FALLO") . "\n";

if ($pid) {
    file_put_contents($pidFile, $pid);
    
    // Esperar a que inicie
    echo "  Esperando respuesta...\n";
    $maxWait = 15;
    for ($i = 0; $i < $maxWait; $i++) {
        sleep(1);
        $ch = @curl_init("http://127.0.0.1:$nodePort/api/health");
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        @curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        $r = @curl_exec($ch);
        $httpCode = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        @curl_close($ch);
        
        if ($httpCode === 200) {
            echo "  Servidor iniciado correctamente (intento " . ($i+1) . ")\n";
            break;
        }
        echo "  Intento " . ($i+1) . ": HTTP $httpCode\n";
    }
}
echo "\n";

// Paso 6: Probar health
echo "[6] Probando /api/health...\n";
$ch = curl_init("http://127.0.0.1:$nodePort/api/health");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5]);
$r = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "  HTTP: $httpCode\n";
echo "  Response: " . ($r ?: "vacio") . "\n\n";

// Paso 7: Probar nexusmk health
echo "[7] Probando /api/nexusmk/health...\n";
$ch = curl_init("http://127.0.0.1:$nodePort/api/nexusmk/health");
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
$r = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "  HTTP: $httpCode\n";
echo "  Response: " . ($r ?: "vacio") . "\n\n";

// Paso 8: Ver logs si hay error
echo "[8] Ultimas lineas del log:\n";
if (file_exists($logFile)) {
    $log = file_get_contents($logFile);
    $lines = explode("\n", $log);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        echo "  " . $line . "\n";
    }
} else {
    echo "  No existe node.log\n";
}

echo "\n=== FIN ===\n";
