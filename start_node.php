<?php
// Iniciar la app Node.js en el puerto 3001
$baseDir = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$nodePath = '/opt/alt/alt-nodejs20/root/usr/bin/node';

// Verificar si ya está corriendo
$fp = @fsockopen('127.0.0.1', 3001, $errno, $errstr, 1);
if ($fp) {
    fclose($fp);
    echo "Node.js app YA está corriendo en puerto 3001\n";
    exit;
}

// Matar procesos node existentes (por si acaso)
exec("pkill -f 'node start.js' 2>/dev/null");
sleep(1);

// Iniciar Node.js en background
$cmd = "cd $baseDir && nohup $nodePath start.js > $baseDir/node_app.log 2>&1 & echo PID: \$!";
exec($cmd, $output);
echo implode("\n", $output) . "\n";

sleep(3);

// Verificar que inició
$fp = @fsockopen('127.0.0.1', 3001, $errno, $errstr, 2);
if ($fp) {
    fclose($fp);
    echo "✅ Node.js app iniciada correctamente en puerto 3001\n";
} else {
    echo "❌ No se pudo iniciar Node.js app\n";
    echo "Log:\n";
    echo file_get_contents($baseDir . '/node_app.log');
}
