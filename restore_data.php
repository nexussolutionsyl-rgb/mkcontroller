<?php
/**
 * Restaura los archivos de datos JSON en el servidor
 * y reinicia el servidor Node.js
 */

header('Content-Type: text/plain; charset=utf-8');
echo "=== RESTAURANDO DATOS JSON ===\n\n";

$baseDir = __DIR__;
$dataDir = $baseDir . '/backend/data';

// Asegurar que el directorio data existe
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
    echo "[OK] Directorio data creado\n";
}

// 1. users.json
$users = [
    [
        "id" => "d9fb0961-7e0b-48ff-bd15-2e6cad77a157",
        "username" => "admin",
        "password" => "\$2a\$10\$gazJ4UI3rf0OHGsz68LSCO4dG4XjhtNX6W18Xc0V/PBprUFLrO3QG",
        "name" => "Super Administrador",
        "email" => "admin@mkcontroller.com",
        "role" => "superadmin",
        "clientId" => null,
        "status" => "active",
        "createdAt" => "2026-06-19T12:03:10.047Z",
        "updatedAt" => "2026-06-19T12:03:10.048Z"
    ],
    [
        "id" => "4737c702-ce2d-4033-9b74-3ae35a440c8c",
        "username" => "demo",
        "password" => "\$2a\$10\$ADEaVMypMDGTQ16FQgEGLeDOCKC.RgOZnjjfAZ.NAoqprZaGaCa6y",
        "name" => "Usuario Demo",
        "email" => "demo@mkcontroller.com",
        "role" => "admin",
        "clientId" => "de533cb4-d24b-4cb6-b021-8fdde1b9fd9a",
        "status" => "active",
        "createdAt" => "2026-06-19T12:03:10.114Z",
        "updatedAt" => "2026-06-19T12:03:10.114Z"
    ]
];

$result = file_put_contents($dataDir . '/users.json', json_encode($users, JSON_PRETTY_PRINT));
echo $result ? "[OK] users.json escrito (" . $result . " bytes)\n" : "[ERROR] No se pudo escribir users.json\n";

// 2. clients.json
$clients = [
    [
        "id" => "de533cb4-d24b-4cb6-b021-8fdde1b9fd9a",
        "name" => "Cliente Demo",
        "company" => "Empresa Demo S.A.",
        "email" => "demo@empresa.com",
        "phone" => "+58 412-1234567",
        "address" => "Av. Principal, Caracas",
        "plan" => "professional",
        "status" => "active",
        "notes" => "Cliente de demostración",
        "createdAt" => "2026-06-19T12:03:10.050Z",
        "updatedAt" => "2026-06-19T12:03:10.050Z"
    ]
];

$result = file_put_contents($dataDir . '/clients.json', json_encode($clients, JSON_PRETTY_PRINT));
echo $result ? "[OK] clients.json escrito (" . $result . " bytes)\n" : "[ERROR] No se pudo escribir clients.json\n";

// 3. routers.json (vacío)
$result = file_put_contents($dataDir . '/routers.json', json_encode([], JSON_PRETTY_PRINT));
echo $result ? "[OK] routers.json escrito (" . $result . " bytes)\n" : "[ERROR] No se pudo escribir routers.json\n";

// 4. hotspot_tickets.json (vacío)
$result = file_put_contents($dataDir . '/hotspot_tickets.json', json_encode([], JSON_PRETTY_PRINT));
echo $result ? "[OK] hotspot_tickets.json escrito (" . $result . " bytes)\n" : "[ERROR] No se pudo escribir hotspot_tickets.json\n";

// 5. activity_log.json (vacío)
$result = file_put_contents($dataDir . '/activity_log.json', json_encode([], JSON_PRETTY_PRINT));
echo $result ? "[OK] activity_log.json escrito (" . $result . " bytes)\n" : "[ERROR] No se pudo escribir activity_log.json\n";

echo "\n--- Verificando archivos escritos ---\n";
$files = ['users.json', 'clients.json', 'routers.json', 'hotspot_tickets.json', 'activity_log.json'];
foreach ($files as $file) {
    $path = $dataDir . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $content = file_get_contents($path);
        echo "[OK] $file: $size bytes, JSON valido: " . (json_decode($content) !== null ? "SI" : "NO") . "\n";
    } else {
        echo "[ERROR] $file NO EXISTE\n";
    }
}

echo "\n--- Reiniciando servidor Node.js ---\n";

// Matar procesos Node.js existentes
exec("pkill -9 -f 'node.*start.js' 2>/dev/null", $output, $exitCode);
echo "[INFO] pkill exit code: $exitCode\n";
sleep(1);

// Verificar si el puerto 3001 está libre
exec("lsof -i :3001 2>/dev/null | grep LISTEN", $listenOutput, $listenCode);
if ($listenCode === 0) {
    echo "[WARN] Puerto 3001 ocupado, forzando cierre...\n";
    exec("fuser -k 3001/tcp 2>/dev/null");
    sleep(1);
} else {
    echo "[OK] Puerto 3001 libre\n";
}

// Iniciar Node.js
$nodePath = "/opt/alt/alt-nodejs20/root/usr/bin/node";
$startScript = $baseDir . "/start.js";
$logFile = $baseDir . "/node.log";

if (file_exists($startScript)) {
    $cmd = "cd $baseDir && nohup $nodePath $startScript > $logFile 2>&1 & echo \$!";
    $pid = exec($cmd);
    echo "[OK] Node.js iniciado con PID: $pid\n";
    sleep(2);
    
    // Verificar que el proceso está corriendo
    exec("ps aux | grep 'node.*start.js' | grep -v grep", $psOutput);
    if (!empty($psOutput)) {
        echo "[OK] Proceso Node.js confirmado:\n";
        foreach ($psOutput as $line) {
            echo "  $line\n";
        }
    } else {
        echo "[WARN] No se pudo confirmar el proceso Node.js\n";
    }
} else {
    echo "[ERROR] start.js no encontrado en $startScript\n";
}

echo "\n--- Probando endpoints ---\n";

// Probar health
$health = @file_get_contents("http://127.0.0.1:3001/api/health");
echo "[Health] " . ($health ?: "SIN RESPUESTA") . "\n";

// Probar login
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode(['username' => 'admin', 'password' => 'superadmin']),
        'timeout' => 5
    ]
]);
$login = @file_get_contents("http://127.0.0.1:3001/api/auth/login", false, $context);
echo "[Login] " . ($login ?: "SIN RESPUESTA") . "\n";

// Probar nexusmk health
$nexusmk = @file_get_contents("http://127.0.0.1:3001/api/nexusmk/health");
echo "[NexusMK] " . ($nexusmk ?: "SIN RESPUESTA") . "\n";

echo "\n=== RESTAURACION COMPLETADA ===\n";
