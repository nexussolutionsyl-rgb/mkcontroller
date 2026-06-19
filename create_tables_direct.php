<?php
/**
 * Script para crear tablas nexusMK directamente
 * Usa el usuario nexusyl_nexusmk (ya creado via API)
 * 
 * EJECUTAR: https://nexusmk.nexussolutionsyl.com/create_tables_direct.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Create Tables nexusMK</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#1a1a2e;color:#e0e0e0;}
h1{color:#00d4ff;}
.success{color:#00ff88;background:#003322;padding:10px;border-radius:5px;margin:5px 0;}
.error{color:#ff4444;background:#330000;padding:10px;border-radius:5px;margin:5px 0;}
.info{color:#ffaa00;background:#332200;padding:10px;border-radius:5px;margin:5px 0;}
pre{background:#16213e;padding:10px;border-radius:5px;overflow-x:auto;}
code{color:#00d4ff;}
</style></head><body>";
echo "<h1>Creacion de Tablas nexusMK</h1>";

// Configuracion - el usuario y BD ya existen en cPanel
$db_host = 'localhost';
$db_user = 'nexusyl_nexusmk';
$db_pass = 'MkController2024!';
$db_name = 'nexusyl_nexusmk';

echo "<div class='info'>Intentando conectar como <code>$db_user</code> a BD <code>$db_name</code>...</div>";

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        throw new Exception("Error: " . $conn->connect_error);
    }
    echo "<div class='success'>Conexion exitosa a MySQL como <code>$db_user</code></div>";
} catch (Exception $e) {
    echo "<div class='error'>" . $e->getMessage() . "</div>";
    echo "<div class='info'>";
    echo "<p><strong>Posibles causas:</strong></p>";
    echo "<ul>";
    echo "<li>El usuario <code>nexusyl_nexusmk</code> no tiene permisos sobre <code>nexusyl_nexusmk</code></li>";
    echo "<li>Ve a cPanel -> MySQL Databases y asigna el usuario a la BD</li>";
    echo "<li>O usa el enlace: <a href='https://nexusmk.nexussolutionsyl.com/setup_nexusmk_db.php' style='color:#00d4ff;'>setup_nexusmk_db.php</a> (necesitas la contrasena de root)</li>";
    echo "</ul>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Crear tablas
$tables = [
    "CREATE TABLE IF NOT EXISTS `usuarios_nexusmk` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nombre` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100),
        `rol` ENUM('superadmin','admin','user') DEFAULT 'user',
        `permisos` JSON,
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS `dispositivos_mikrotik` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(100) NOT NULL,
        `ip` VARCHAR(45) NOT NULL,
        `puerto_api` INT DEFAULT 8728,
        `puerto_winbox` INT DEFAULT 8291,
        `puerto_ssh` INT DEFAULT 22,
        `usuario` VARCHAR(50) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `tipo` ENUM('router','switch','access_point','firewall','otros') DEFAULT 'router',
        `ubicacion` VARCHAR(100),
        `version_routeros` VARCHAR(20),
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS `interfaces_wireguard` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `dispositivo_id` INT NOT NULL,
        `nombre` VARCHAR(50) NOT NULL,
        `puerto` INT DEFAULT 51820,
        `direccion_ip` VARCHAR(45),
        `clave_privada` TEXT,
        `clave_publica` TEXT,
        `mtu` INT DEFAULT 1420,
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_mikrotik`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS `peers_wireguard` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `interfaz_id` INT NOT NULL,
        `nombre` VARCHAR(100) NOT NULL,
        `clave_publica` TEXT NOT NULL,
        `direccion_ip` VARCHAR(45),
        `puerto` INT DEFAULT 51820,
        `endpoint` VARCHAR(255),
        `allowed_ips` VARCHAR(255),
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`interfaz_id`) REFERENCES `interfaces_wireguard`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS `reglas_firewall` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `dispositivo_id` INT NOT NULL,
        `nombre` VARCHAR(100),
        `cadena` VARCHAR(50) DEFAULT 'forward',
        `accion` VARCHAR(50) DEFAULT 'accept',
        `protocolo` VARCHAR(20),
        `puerto_origen` INT,
        `puerto_destino` INT,
        `ip_origen` VARCHAR(45),
        `ip_destino` VARCHAR(45),
        `orden` INT DEFAULT 0,
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_mikrotik`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "CREATE TABLE IF NOT EXISTS `configuracion_general` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `clave` VARCHAR(100) NOT NULL UNIQUE,
        `valor` TEXT,
        `descripcion` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$tableNames = ['usuarios_nexusmk', 'dispositivos_mikrotik', 'interfaces_wireguard', 'peers_wireguard', 'reglas_firewall', 'configuracion_general'];

foreach ($tables as $i => $sql) {
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>{$tableNames[$i]}</code> creada/verificada</div>";
    } else {
        echo "<div class='error'>Error creando {$tableNames[$i]}: " . $conn->error . "</div>";
    }
}

// Insertar admin user
$adminPass = password_hash('admin123', PASSWORD_BCRYPT);
$permisos = json_encode(['all' => true]);
$stmt = $conn->prepare("INSERT IGNORE INTO `usuarios_nexusmk` (`username`, `password`, `nombre`, `email`, `rol`, `permisos`, `activo`) VALUES (?, ?, ?, ?, 'superadmin', ?, 1)");
$username = 'admin';
$nombre = 'Administrador';
$email = 'admin@nexussolutionsyl.com';
$stmt->bind_param('sssss', $username, $adminPass, $nombre, $email, $permisos);
if ($stmt->execute()) {
    echo "<div class='success'>Usuario admin creado: <code>admin</code> / <code>admin123</code></div>";
} else {
    echo "<div class='info'>Usuario admin ya existe o error: " . $stmt->error . "</div>";
}

// Insertar config default
$configs = [
    ['app_name', 'MkController v3.0', 'Nombre de la aplicacion'],
    ['app_version', '3.0.0', 'Version de la aplicacion'],
    ['db_version', '1.0.0', 'Version del esquema de BD'],
    ['session_timeout', '3600', 'Tiempo de sesion en segundos'],
    ['max_dispositivos', '50', 'Maximo de dispositivos por cliente'],
    ['hotspot_ticket_prefix', 'MK-', 'Prefijo para tickets hotspot'],
    ['monitoreo_intervalo', '60', 'Intervalo de monitoreo en segundos']
];
foreach ($configs as $cfg) {
    $stmt = $conn->prepare("INSERT IGNORE INTO `configuracion_general` (`clave`, `valor`, `descripcion`) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $cfg[0], $cfg[1], $cfg[2]);
    $stmt->execute();
}
echo "<div class='success'>Configuracion inicial insertada</div>";

$conn->close();

echo "<div class='success' style='font-size:1.2em;padding:15px;'>";
echo "<strong>BASE DE DATOS CONFIGURADA EXITOSAMENTE</strong><br>";
echo "BD: <code>$db_name</code><br>";
echo "Usuario: <code>$db_user</code><br>";
echo "Contrasena: <code>$db_pass</code><br>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>Proximo paso:</strong> Actualizar .env en el servidor con:<br>";
echo "<pre>NEXUSMK_DB_HOST=localhost
NEXUSMK_DB_USER=$db_user
NEXUSMK_DB_PASSWORD=$db_pass
NEXUSMK_DB_NAME=$db_name</pre>";
echo "</div>";

echo "</body></html>";
