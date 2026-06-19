<?php
/**
 * Script que ejecuta la configuracion de BD nexusMK directamente
 * usando la contrasena root proporcionada
 * Guarda resultado en setup_db_log.txt
 */

header('Content-Type: text/plain; charset=utf-8');

$root_pass = 'Casita.2026';
$db_host = 'localhost';
$db_name = 'nexusyl_nexusmk';
$db_user = 'nexusyl_nexusmk';
$db_pass = 'MkController2024!';

$log = [];
$log[] = "=== INICIANDO CONFIGURACION BD nexusMK ===";
$log[] = "Fecha: " . date('Y-m-d H:i:s');
$log[] = "";

// Conectar como root
$conn = @new mysqli($db_host, 'nexusyl_root', $root_pass);
if ($conn->connect_error) {
    $log[] = "ERROR: No se pudo conectar como root: " . $conn->connect_error;
    file_put_contents('setup_db_log.txt', implode("\n", $log));
    echo implode("\n", $log);
    exit;
}
$log[] = "OK: Conexion como root exitosa";

// PASO 1: Asignar privilegios
$log[] = "";
$log[] = "--- Paso 1: Asignando privilegios ---";

$grant_queries = [
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'",
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'127.0.0.1'",
    "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'%'",
    "FLUSH PRIVILEGES"
];

foreach ($grant_queries as $q) {
    if ($conn->query($q)) {
        $log[] = "OK: " . substr($q, 0, 60) . "...";
    } else {
        $log[] = "INFO: " . substr($q, 0, 60) . "... -> " . $conn->error;
    }
}
$conn->close();

// PASO 2: Verificar acceso del usuario
$log[] = "";
$log[] = "--- Paso 2: Verificando acceso del usuario ---";

$conn2 = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn2->connect_error) {
    $log[] = "ERROR: Usuario $db_user NO tiene acceso a $db_name: " . $conn2->connect_error;
    file_put_contents('setup_db_log.txt', implode("\n", $log));
    echo implode("\n", $log);
    exit;
}
$log[] = "OK: Usuario $db_user tiene acceso a $db_name";

// PASO 3: Crear tablas
$log[] = "";
$log[] = "--- Paso 3: Creando tablas ---";

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

foreach ($tables_sql as $sql) {
    $table_name = '';
    if (preg_match('/CREATE TABLE.*?`(\w+)`/', $sql, $m)) {
        $table_name = $m[1];
    }
    if ($conn2->query($sql)) {
        $log[] = "OK: Tabla '$table_name' creada/verificada";
    } else {
        $log[] = "ERROR: Tabla '$table_name': " . $conn2->error;
    }
}

// PASO 4: Insertar datos iniciales
$log[] = "";
$log[] = "--- Paso 4: Insertando datos iniciales ---";

$admin_password = password_hash('admin123', PASSWORD_BCRYPT);
$insert_admin = "INSERT IGNORE INTO `usuarios_nexusmk` 
    (`username`, `password`, `nombre`, `email`, `rol`, `permisos`, `activo`) 
    VALUES ('admin', '$admin_password', 'Administrador', 'admin@nexusmk.local', 'admin', 
    '{\"dispositivos\":true,\"wireguard\":true,\"firewall\":true,\"usuarios\":true,\"configuracion\":true}', 1)";

if ($conn2->query($insert_admin)) {
    $log[] = "OK: Usuario admin creado (admin/admin123)";
} else {
    $log[] = "INFO: Usuario admin ya existe o error: " . $conn2->error;
}

$config_defaults = [
    ['app_nombre', 'nexusMK Controller', 'Nombre de la aplicacion'],
    ['app_version', '1.0.0', 'Version de la aplicacion'],
    ['tiempo_reconexion', '30', 'Tiempo de reconexion en segundos'],
    ['max_dispositivos', '10', 'Maximo de dispositivos por cliente'],
    ['log_actividades', '1', 'Activar log de actividades'],
    ['tema_oscuro', '1', 'Tema oscuro por defecto']
];

foreach ($config_defaults as $cfg) {
    $insert_cfg = "INSERT IGNORE INTO `configuracion_general` 
        (`clave`, `valor`, `descripcion`) 
        VALUES ('{$cfg[0]}', '{$cfg[1]}', '{$cfg[2]}')";
    if ($conn2->query($insert_cfg)) {
        $log[] = "OK: Config '{$cfg[0]}' = '{$cfg[1]}'";
    }
}

$conn2->close();

$log[] = "";
$log[] = "=== CONFIGURACION COMPLETADA EXITOSAMENTE ===";
$log[] = "Base de datos: $db_name";
$log[] = "Usuario: $db_user";
$log[] = "Password: $db_pass";

file_put_contents('setup_db_log.txt', implode("\n", $log));
echo implode("\n", $log);
?>
