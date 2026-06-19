<?php
/**
 * Script MINIMAL para configurar BD nexusMK
 * Usa texto plano, paso a paso, con flush()
 */
header('Content-Type: text/plain; charset=utf-8');
ob_implicit_flush(true);
ob_end_flush();

echo "=== INICIANDO SETUP BD nexusMK ===\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n\n";

$root_pass = 'Casita.2026';
$db_host = 'localhost';
$db_name = 'nexusyl_nexusmk';
$db_user = 'nexusyl_nexusmk';
$db_pass = 'MkController2024!';

// PASO 1: Conectar como root
echo "Paso 1: Conectando como root...\n";
$conn = @new mysqli($db_host, 'nexusyl_root', $root_pass);
if ($conn->connect_error) {
    echo "ERROR: " . $conn->connect_error . "\n";
    exit(1);
}
echo "OK: Conexion root exitosa\n\n";

// PASO 2: Asignar privilegios
echo "Paso 2: Asignando privilegios...\n";
$queries = [
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'",
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'127.0.0.1'",
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'%'",
    "FLUSH PRIVILEGES"
];
foreach ($queries as $q) {
    if ($conn->query($q)) {
        echo "  OK: " . substr($q, 0, 50) . "...\n";
    } else {
        echo "  INFO: " . $conn->error . "\n";
    }
}
$conn->close();
echo "\n";

// PASO 3: Verificar acceso del usuario
echo "Paso 3: Verificando acceso de $db_user...\n";
$conn2 = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn2->connect_error) {
    echo "ERROR: $db_user NO tiene acceso: " . $conn2->connect_error . "\n";
    exit(1);
}
echo "OK: $db_user tiene acceso a $db_name\n\n";

// PASO 4: Crear tablas
echo "Paso 4: Creando tablas...\n";

$tables_sql = [
    "CREATE TABLE IF NOT EXISTS `usuarios_nexusmk` (
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
    
    "CREATE TABLE IF NOT EXISTS `dispositivos_mikrotik` (
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
    
    "CREATE TABLE IF NOT EXISTS `interfaces_wireguard` (
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
    
    "CREATE TABLE IF NOT EXISTS `peers_wireguard` (
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
    
    "CREATE TABLE IF NOT EXISTS `reglas_firewall` (
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
    
    "CREATE TABLE IF NOT EXISTS `configuracion_general` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `clave` VARCHAR(100) NOT NULL UNIQUE,
        `valor` TEXT,
        `descripcion` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$table_names = [
    'usuarios_nexusmk',
    'dispositivos_mikrotik',
    'interfaces_wireguard',
    'peers_wireguard',
    'reglas_firewall',
    'configuracion_general'
];

foreach ($tables_sql as $i => $sql) {
    if ($conn2->query($sql)) {
        echo "  OK: Tabla '{$table_names[$i]}' creada\n";
    } else {
        echo "  ERROR: '{$table_names[$i]}': " . $conn2->error . "\n";
    }
}
echo "\n";

// PASO 5: Insertar admin
echo "Paso 5: Insertando usuario admin...\n";
$admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
$r = $conn2->query("INSERT IGNORE INTO `usuarios_nexusmk` 
    (`username`, `password`, `nombre`, `email`, `rol`, `permisos`, `activo`) 
    VALUES ('admin', '$admin_pass', 'Administrador', 'admin@nexusmk.local', 'admin', 
    '{\"dispositivos\":true,\"wireguard\":true,\"firewall\":true,\"usuarios\":true,\"configuracion\":true}', 1)");
echo ($r ? "  OK: Usuario admin creado (admin/admin123)" : "  INFO: Admin ya existe") . "\n\n";

// PASO 6: Config defaults
echo "Paso 6: Insertando configuracion por defecto...\n";
$configs = [
    ['app_nombre', 'nexusMK Controller'],
    ['app_version', '1.0.0'],
    ['tiempo_reconexion', '30'],
    ['max_dispositivos', '10'],
    ['log_actividades', '1'],
    ['tema_oscuro', '1']
];
foreach ($configs as $c) {
    $conn2->query("INSERT IGNORE INTO `configuracion_general` 
        (`clave`, `valor`) VALUES ('{$c[0]}', '{$c[1]}')");
    echo "  OK: config '{$c[0]}' = '{$c[1]}'\n";
}

$conn2->close();
echo "\n=== SETUP COMPLETADO EXITOSAMENTE ===\n";
echo "Base de datos: $db_name\n";
echo "Usuario: $db_user\n";
echo "Password: $db_pass\n";
echo "Admin nexusMK: admin / admin123\n";
?>
