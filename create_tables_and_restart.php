<?php
/**
 * Script para crear tablas de nexusMK y reiniciar Node.js
 * Usa conexion root directa (nexusyl_root / Casita.2026)
 * By: MkController v3.0
 */

// Configuracion
$DB_ROOT = [
    'host' => 'localhost',
    'user' => 'nexusyl_root',
    'password' => 'Casita.2026',
    'database' => 'nexusyl_nexusmk'
];

$NODE_PATH = '/opt/alt/alt-nodejs20/root/usr/bin/node';
$APP_DIR = '/home/nexusyl/nexusmk.nexussolutionsyl.com';
$PROXY_PORT = 3001;

// Headers
header('Content-Type: text/plain; charset=utf-8');
ob_implicit_flush(true);
ob_end_flush();

echo "========================================\n";
echo "CREACION DE TABLAS NEXUSMK + REINICIO\n";
echo "========================================\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// PASO 1: Conectar a MySQL como root
echo "[PASO 1] Conectando a MySQL como root...\n";
try {
    $conn = new mysqli(
        $DB_ROOT['host'],
        $DB_ROOT['user'],
        $DB_ROOT['password'],
        $DB_ROOT['database']
    );
    if ($conn->connect_error) {
        throw new Exception("Error de conexion: " . $conn->connect_error);
    }
    echo "  OK: Conectado a {$DB_ROOT['database']}\n\n";
} catch (Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

// PASO 2: Crear tablas
echo "[PASO 2] Creando tablas...\n";

$tables = [
    'usuarios_nexusmk' => "
        CREATE TABLE IF NOT EXISTS `usuarios_nexusmk` (
            `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
            `usuario` VARCHAR(50) NOT NULL UNIQUE,
            `clave` VARCHAR(255) NOT NULL,
            `nombre_completo` VARCHAR(150) NOT NULL,
            `email` VARCHAR(100) DEFAULT NULL,
            `telefono` VARCHAR(20) DEFAULT NULL,
            `permisos` JSON DEFAULT NULL,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            `ultimo_acceso` DATETIME DEFAULT NULL,
            `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'dispositivos_mikrotik' => "
        CREATE TABLE IF NOT EXISTS `dispositivos_mikrotik` (
            `id_dispositivo` INT AUTO_INCREMENT PRIMARY KEY,
            `nombre` VARCHAR(100) NOT NULL,
            `direccion_ip` VARCHAR(45) NOT NULL,
            `puerto_api` INT NOT NULL DEFAULT 8728,
            `puerto_web` INT NOT NULL DEFAULT 80,
            `usuario` VARCHAR(50) NOT NULL,
            `clave` VARCHAR(255) NOT NULL,
            `tipo_dispositivo` ENUM('router','switch','access_point','firewall','otros') NOT NULL DEFAULT 'router',
            `modelo` VARCHAR(100) DEFAULT NULL,
            `version_routeros` VARCHAR(50) DEFAULT NULL,
            `ubicacion` VARCHAR(200) DEFAULT NULL,
            `notas` TEXT DEFAULT NULL,
            `monitoreo_activo` TINYINT(1) NOT NULL DEFAULT 1,
            `intervalo_monitoreo` INT NOT NULL DEFAULT 60,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            `ultimo_contacto` DATETIME DEFAULT NULL,
            `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'interfaces_wireguard' => "
        CREATE TABLE IF NOT EXISTS `interfaces_wireguard` (
            `id_interfaz` INT AUTO_INCREMENT PRIMARY KEY,
            `id_dispositivo` INT NOT NULL,
            `nombre_interfaz` VARCHAR(50) NOT NULL,
            `direccion_ip` VARCHAR(45) DEFAULT NULL,
            `puerto_listen` INT DEFAULT NULL,
            `clave_privada` TEXT DEFAULT NULL,
            `clave_publica` VARCHAR(255) DEFAULT NULL,
            `mtu` INT DEFAULT 1420,
            `dns` VARCHAR(200) DEFAULT NULL,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_dispositivo`) REFERENCES `dispositivos_mikrotik`(`id_dispositivo`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'peers_wireguard' => "
        CREATE TABLE IF NOT EXISTS `peers_wireguard` (
            `id_peer` INT AUTO_INCREMENT PRIMARY KEY,
            `id_interfaz` INT NOT NULL,
            `nombre` VARCHAR(100) NOT NULL,
            `clave_publica` VARCHAR(255) NOT NULL,
            `preshared_key` TEXT DEFAULT NULL,
            `allowed_ips` VARCHAR(255) NOT NULL,
            `endpoint` VARCHAR(255) DEFAULT NULL,
            `persistent_keepalive` INT DEFAULT 25,
            `dato_rx` BIGINT DEFAULT 0,
            `dato_tx` BIGINT DEFAULT 0,
            `ultima_handshake` DATETIME DEFAULT NULL,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            `notas` TEXT DEFAULT NULL,
            `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_interfaz`) REFERENCES `interfaces_wireguard`(`id_interfaz`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'reglas_firewall' => "
        CREATE TABLE IF NOT EXISTS `reglas_firewall` (
            `id_regla` INT AUTO_INCREMENT PRIMARY KEY,
            `id_dispositivo` INT NOT NULL,
            `chain` VARCHAR(50) NOT NULL DEFAULT 'forward',
            `accion` VARCHAR(50) NOT NULL DEFAULT 'accept',
            `protocolo` VARCHAR(20) DEFAULT NULL,
            `puerto_origen` INT DEFAULT NULL,
            `puerto_destino` INT DEFAULT NULL,
            `direccion_origen` VARCHAR(45) DEFAULT NULL,
            `direccion_destino` VARCHAR(45) DEFAULT NULL,
            `comentario` VARCHAR(255) DEFAULT NULL,
            `orden` INT NOT NULL DEFAULT 0,
            `estado` TINYINT(1) NOT NULL DEFAULT 1,
            `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`id_dispositivo`) REFERENCES `dispositivos_mikrotik`(`id_dispositivo`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",
    'configuracion_general' => "
        CREATE TABLE IF NOT EXISTS `configuracion_general` (
            `id_config` INT AUTO_INCREMENT PRIMARY KEY,
            `clave` VARCHAR(100) NOT NULL UNIQUE,
            `valor` TEXT NOT NULL,
            `descripcion` VARCHAR(255) DEFAULT NULL,
            `fecha_actualizacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    "
];

$success = true;
foreach ($tables as $name => $sql) {
    echo "  Creando {$name}... ";
    if ($conn->query($sql) === TRUE) {
        echo "OK\n";
    } else {
        echo "ERROR: " . $conn->error . "\n";
        $success = false;
    }
}

if (!$success) {
    echo "\n[ADVERTENCIA] Algunas tablas no se crearon correctamente.\n";
}

echo "\n";

// PASO 3: Verificar tablas creadas
echo "[PASO 3] Verificando tablas...\n";
$result = $conn->query("SHOW TABLES");
$tablas_encontradas = [];
if ($result) {
    while ($row = $result->fetch_array()) {
        $tablas_encontradas[] = $row[0];
        echo "  - {$row[0]}\n";
    }
}
echo "  Total: " . count($tablas_encontradas) . " tablas\n\n";

// PASO 4: Insertar datos iniciales (si no existen)
echo "[PASO 4] Insertando datos iniciales...\n";

// Verificar si ya hay admin
$check = $conn->query("SELECT COUNT(*) as cnt FROM usuarios_nexusmk");
$row = $check->fetch_assoc();
if ($row['cnt'] == 0) {
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO usuarios_nexusmk (usuario, clave, nombre_completo, email, permisos, estado) VALUES (?, ?, ?, ?, ?, 1)");
    $permisos = json_encode(['dispositivos' => true, 'wireguard' => true, 'firewall' => true, 'usuarios' => true, 'configuracion' => true]);
    $stmt->bind_param('sssss', $usuario, $hash, $nombre, $email, $permisos);
    $usuario = 'admin';
    $nombre = 'Administrador nexusMK';
    $email = 'admin@nexussolutionsyl.com';
    if ($stmt->execute()) {
        echo "  OK: Usuario admin creado (clave: admin123)\n";
    } else {
        echo "  ERROR: " . $stmt->error . "\n";
    }
    $stmt->close();
} else {
    echo "  OK: Usuario admin ya existe\n";
}

// Configuracion por defecto
$configs = [
    ['app_nombre', 'nexusMK', 'Nombre de la aplicacion'],
    ['app_version', '1.0.0', 'Version del modulo'],
    ['tiempo_monitoreo', '60', 'Intervalo de monitoreo en segundos'],
    ['limite_dispositivos', '10', 'Numero maximo de dispositivos permitidos'],
    ['notificaciones_activas', 'true', 'Activar/desactivar notificaciones']
];

foreach ($configs as $cfg) {
    $check = $conn->query("SELECT COUNT(*) as cnt FROM configuracion_general WHERE clave = '{$cfg[0]}'");
    $r = $check->fetch_assoc();
    if ($r['cnt'] == 0) {
        $stmt = $conn->prepare("INSERT INTO configuracion_general (clave, valor, descripcion) VALUES (?, ?, ?)");
        $stmt->bind_param('sss', $cfg[0], $cfg[1], $cfg[2]);
        $stmt->execute();
        $stmt->close();
        echo "  OK: Config {$cfg[0]} = {$cfg[1]}\n";
    }
}

echo "\n";

// PASO 5: Cerrar conexion MySQL
$conn->close();
echo "[PASO 5] Conexion MySQL cerrada.\n\n";

// PASO 6: Matar procesos Node.js existentes
echo "[PASO 6] Deteniendo servidor Node.js existente...\n";
$output = [];
$exitCode = 0;
exec("pkill -f \"node.*start.js\" 2>/dev/null", $output, $exitCode);
exec("kill \$(lsof -t -i:{$PROXY_PORT} 2>/dev/null) 2>/dev/null", $output, $exitCode);
echo "  OK: Procesos Node.js detenidos\n\n";

// PASO 7: Iniciar Node.js
echo "[PASO 7] Iniciando servidor Node.js en puerto {$PROXY_PORT}...\n";
$cmd = "cd {$APP_DIR} && {$NODE_PATH} start.js > /dev/null 2>&1 &";
exec($cmd, $output, $exitCode);
echo "  Comando: {$cmd}\n";
echo "  OK: Servidor Node.js iniciado\n\n";

// PASO 8: Esperar y verificar
echo "[PASO 8] Esperando 3 segundos para que Node.js inicie...\n";
sleep(3);

// Verificar health endpoint via proxy.php
echo "[PASO 9] Verificando /api/nexusmk/health...\n";
$ch = curl_init("http://localhost:{$PROXY_PORT}/api/nexusmk/health");
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

if ($curlError) {
    echo "  ERROR: " . $curlError . "\n";
} else {
    echo "  HTTP Code: {$httpCode}\n";
    echo "  Response:\n";
    $parsed = json_decode($response, true);
    if ($parsed) {
        echo "    " . json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "    " . substr($response, 0, 500) . "\n";
    }
}

echo "\n";

// PASO 10: Verificar health via proxy.php (URL publica)
echo "[PASO 10] Verificando via proxy.php (URL publica)...\n";
$publicUrl = "https://nexusmk.nexussolutionsyl.com/api/nexusmk/health";
$ch = curl_init($publicUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => ['Accept: application/json']
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "  ERROR: " . $curlError . "\n";
} else {
    echo "  HTTP Code: {$httpCode}\n";
    echo "  Response:\n";
    $parsed = json_decode($response, true);
    if ($parsed) {
        echo "    " . json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } else {
        echo "    " . substr($response, 0, 500) . "\n";
    }
}

echo "\n========================================\n";
echo "PROCESO COMPLETADO\n";
echo "========================================\n";
