<?php
/**
 * Script simple para configurar BD nexusMK
 * Usa root password para asignar privilegios y crear tablas
 */
header('Content-Type: text/plain; charset=utf-8');

$root_pass = 'Casita.2026';
$db_host = 'localhost';
$db_name = 'nexusyl_nexusmk';
$db_user = 'nexusyl_nexusmk';
$db_pass = 'MkController2024!';

echo "=== INICIANDO CONFIGURACION BD nexusMK ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

// Conectar como root
$conn = @new mysqli($db_host, 'nexusyl_root', $root_pass);
if ($conn->connect_error) {
    die("ERROR: No se pudo conectar como root: " . $conn->connect_error . "\n");
}
echo "OK: Conexion como root exitosa\n\n";

// Asignar privilegios
echo "--- Asignando privilegios ---\n";
$queries = [
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'",
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'127.0.0.1'",
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'%'",
    "FLUSH PRIVILEGES"
];
foreach ($queries as $q) {
    if ($conn->query($q)) echo "OK: " . substr($q, 0, 50) . "...\n";
    else echo "INFO: " . $conn->error . "\n";
}
$conn->close();

// Verificar acceso
echo "\n--- Verificando acceso ---\n";
$conn2 = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn2->connect_error) {
    die("ERROR: Usuario $db_user NO tiene acceso: " . $conn2->connect_error . "\n");
}
echo "OK: Usuario $db_user tiene acceso a $db_name\n\n";

// Crear tablas
echo "--- Creando tablas ---\n";
$tables = [
    "usuarios_nexusmk" => "CREATE TABLE IF NOT EXISTS `usuarios_nexusmk` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nombre` VARCHAR(100) NOT NULL,
        `email` VARCHAR(100),
        `rol` ENUM('admin','operador','lectura') DEFAULT 'operador',
        `permisos` JSON,
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "dispositivos_mikrotik" => "CREATE TABLE IF NOT EXISTS `dispositivos_mikrotik` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nombre` VARCHAR(100) NOT NULL,
        `ip` VARCHAR(45) NOT NULL,
        `puerto_api` INT DEFAULT 8728,
        `puerto_winbox` INT DEFAULT 8291,
        `usuario` VARCHAR(50) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `tipo` ENUM('RouterBoard','CloudCore','CHR','Virtual','Otro') DEFAULT 'RouterBoard',
        `version_routeros` VARCHAR(20),
        `ubicacion` VARCHAR(100),
        `notas` TEXT,
        `activo` TINYINT(1) DEFAULT 1,
        `ultimo_ping` TIMESTAMP NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "interfaces_wireguard" => "CREATE TABLE IF NOT EXISTS `interfaces_wireguard` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `dispositivo_id` INT NOT NULL,
        `nombre` VARCHAR(50) NOT NULL,
        `puerto` INT DEFAULT 13231,
        `llave_privada` TEXT,
        `llave_publica` VARCHAR(255),
        `ip_local` VARCHAR(45),
        `mtu` INT DEFAULT 1420,
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_mikrotik`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "peers_wireguard" => "CREATE TABLE IF NOT EXISTS `peers_wireguard` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `interfaz_id` INT NOT NULL,
        `nombre` VARCHAR(100) NOT NULL,
        `llave_publica` VARCHAR(255) NOT NULL,
        `ip_asignada` VARCHAR(45),
        `puerto` INT DEFAULT 13231,
        `endpoint` VARCHAR(255),
        `keepalive` INT DEFAULT 25,
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`interfaz_id`) REFERENCES `interfaces_wireguard`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "reglas_firewall" => "CREATE TABLE IF NOT EXISTS `reglas_firewall` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `dispositivo_id` INT NOT NULL,
        `nombre` VARCHAR(100),
        `cadena` ENUM('input','forward','output') DEFAULT 'forward',
        `accion` ENUM('accept','drop','reject','masquerade','src-nat') DEFAULT 'accept',
        `protocolo` VARCHAR(10) DEFAULT 'tcp',
        `puerto_destino` INT,
        `puerto_origen` INT,
        `ip_origen` VARCHAR(45),
        `ip_destino` VARCHAR(45),
        `comentario` TEXT,
        `orden` INT DEFAULT 0,
        `activo` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`dispositivo_id`) REFERENCES `dispositivos_mikrotik`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "configuracion_general" => "CREATE TABLE IF NOT EXISTS `configuracion_general` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `clave` VARCHAR(100) NOT NULL UNIQUE,
        `valor` TEXT,
        `descripcion` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $name => $sql) {
    if ($conn2->query($sql)) echo "OK: Tabla '$name' creada\n";
    else echo "ERROR: Tabla '$name': " . $conn2->error . "\n";
}

// Insertar admin
echo "\n--- Insertando datos iniciales ---\n";
$admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
$r = $conn2->query("INSERT IGNORE INTO `usuarios_nexusmk` 
    (`username`, `password`, `nombre`, `email`, `rol`, `permisos`, `activo`) 
    VALUES ('admin', '$admin_pass', 'Administrador', 'admin@nexusmk.local', 'admin', 
    '{\"dispositivos\":true,\"wireguard\":true,\"firewall\":true,\"usuarios\":true,\"configuracion\":true}', 1)");
if ($r) echo "OK: Usuario admin creado (admin/admin123)\n";
else echo "INFO: " . $conn2->error . "\n";

// Config defaults
$configs = [
    ['app_nombre', 'nexusMK Controller', 'Nombre de la aplicacion'],
    ['app_version', '1.0.0', 'Version'],
    ['tiempo_reconexion', '30', 'Reconexion en segundos'],
    ['max_dispositivos', '10', 'Max dispositivos'],
    ['log_actividades', '1', 'Activar log'],
    ['tema_oscuro', '1', 'Tema oscuro']
];
foreach ($configs as $c) {
    $conn2->query("INSERT IGNORE INTO `configuracion_general` 
        (`clave`, `valor`, `descripcion`) VALUES ('{$c[0]}', '{$c[1]}', '{$c[2]}')");
}
echo "OK: Configuracion por defecto insertada\n";

$conn2->close();
echo "\n=== CONFIGURACION COMPLETADA EXITOSAMENTE ===\n";
echo "BD: $db_name\nUsuario: $db_user\nPassword: $db_pass\n";
?>
