configurra<?php
/**
 * Script para asignar privilegios MySQL y crear tablas nexusMK
 * Ejecuta GRANT y CREATE TABLE directamente desde el servidor
 * 
 * EJECUTAR: https://nexusmk.nexussolutionsyl.com/grant_privileges.php
 * LUEGO ELIMINAR por seguridad
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Grant Privileges nexusMK</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#1a1a2e;color:#e0e0e0;}
h1{color:#00d4ff;}
.success{color:#00ff88;background:#003322;padding:10px;border-radius:5px;margin:5px 0;}
.error{color:#ff4444;background:#330000;padding:10px;border-radius:5px;margin:5px 0;}
.info{color:#ffaa00;background:#332200;padding:10px;border-radius:5px;margin:5px 0;}
pre{background:#16213e;padding:10px;border-radius:5px;overflow-x:auto;}
code{color:#00d4ff;}
</style></head><body>";
echo "<h1>Configuracion Base de Datos nexusMK</h1>";

// Obtener credenciales del formulario o usar defaults de cPanel
$db_root_user = 'nexusyl_root';
$db_root_pass = '';
$db_user = 'nexusyl_nexusmk';
$db_pass = 'MkController2024!';
$db_name = 'nexusyl_nexusmk';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_root_pass = $_POST['db_root_pass'] ?? '';
    $db_user = $_POST['db_user'] ?? $db_user;
    $db_pass = $_POST['db_pass'] ?? $db_pass;
    $db_name = $_POST['db_name'] ?? $db_name;
    
    echo "<h2>Resultados:</h2>";
    
    // 1. Conectar como root
    try {
        $conn = new mysqli('localhost', $db_root_user, $db_root_pass);
        if ($conn->connect_error) {
            throw new Exception("Error de conexion como root: " . $conn->connect_error);
        }
        echo "<div class='success'>Conexion como <code>$db_root_user</code> exitosa</div>";
    } catch (Exception $e) {
        echo "<div class='error'>" . $e->getMessage() . "</div>";
        echo "<div class='info'>Usa la contrasena de MySQL root que configuraste en cPanel</div>";
        echo "</body></html>";
        exit;
    }
    
    // 2. Asignar privilegios
    $grantSql = "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'";
    if ($conn->query($grantSql)) {
        echo "<div class='success'>Privilegios asignados: <code>$db_user</code> -> <code>$db_name</code></div>";
    } else {
        echo "<div class='error'>Error asignando privilegios: " . $conn->error . "</div>";
    }
    
    $conn->query("FLUSH PRIVILEGES");
    
    // 3. Conectar a la BD y crear tablas
    $conn->select_db($db_name);
    
    // Tabla: usuarios_nexusmk
    $sql = "CREATE TABLE IF NOT EXISTS `usuarios_nexusmk` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>usuarios_nexusmk</code> creada/verificada</div>";
    } else {
        echo "<div class='error'>Error creando usuarios_nexusmk: " . $conn->error . "</div>";
    }
    
    // Tabla: dispositivos_mikrotik
    $sql = "CREATE TABLE IF NOT EXISTS `dispositivos_mikrotik` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>dispositivos_mikrotik</code> creada/verificada</div>";
    } else {
        echo "<div class='error'>Error creando dispositivos_mikrotik: " . $conn->error . "</div>";
    }
    
    // Tabla: interfaces_wireguard
    $sql = "CREATE TABLE IF NOT EXISTS `interfaces_wireguard` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>interfaces_wireguard</code> creada/verificada</div>";
    } else {
        echo "<div class='error'>Error creando interfaces_wireguard: " . $conn->error . "</div>";
    }
    
    // Tabla: peers_wireguard
    $sql = "CREATE TABLE IF NOT EXISTS `peers_wireguard` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>peers_wireguard</code> creada/verificada</div>";
    } else {
        echo "<div class='error'>Error creando peers_wireguard: " . $conn->error . "</div>";
    }
    
    // Tabla: reglas_firewall
    $sql = "CREATE TABLE IF NOT EXISTS `reglas_firewall` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>reglas_firewall</code> creada/verificada</div>";
    } else {
        echo "<div class='error'>Error creando reglas_firewall: " . $conn->error . "</div>";
    }
    
    // Tabla: configuracion_general
    $sql = "CREATE TABLE IF NOT EXISTS `configuracion_general` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `clave` VARCHAR(100) NOT NULL UNIQUE,
        `valor` TEXT,
        `descripcion` VARCHAR(255),
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    if ($conn->query($sql)) {
        echo "<div class='success'>Tabla <code>configuracion_general</code> creada/verificada</div>";
    } else {
        echo "<div class='error'>Error creando configuracion_general: " . $conn->error . "</div>";
    }
    
    // 4. Insertar admin user
    $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT IGNORE INTO `usuarios_nexusmk` (`username`, `password`, `nombre`, `email`, `rol`, `permisos`, `activo`) VALUES (?, ?, ?, ?, 'superadmin', ?, 1)");
    $permisos = json_encode(['all' => true]);
    $username = 'admin';
    $nombre = 'Administrador';
    $email = 'admin@nexussolutionsyl.com';
    $stmt->bind_param('sssss', $username, $adminPass, $nombre, $email, $permisos);
    if ($stmt->execute()) {
        echo "<div class='success'>Usuario admin creado: <code>admin</code> / <code>admin123</code></div>";
    } else {
        echo "<div class='info'>Usuario admin ya existe o error: " . $stmt->error . "</div>";
    }
    
    // 5. Insertar config default
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
    echo "Contrasena: <code>$db_pass</code><br><br>";
    echo "<strong>Proximo paso:</strong> Actualizar .env en el servidor con:<br>";
    echo "<pre>NEXUSMK_DB_HOST=localhost
NEXUSMK_DB_USER=$db_user
NEXUSMK_DB_PASSWORD=$db_pass
NEXUSMK_DB_NAME=$db_name</pre>";
    echo "</div>";
    
    echo "<div class='info'><strong>IMPORTANTE:</strong> Elimina este archivo del servidor por seguridad</div>";
    
} else {
    // Mostrar formulario
    echo "<div class='info'>
        <strong>PASO 1:</strong> Asegurate de haber creado en cPanel -> MySQL Databases:
        <ul>
            <li>Base de datos: <code>nexusyl_nexusmk</code> - <span class='success'>CREADA</span></li>
            <li>Usuario: <code>nexusyl_nexusmk</code> - <span class='success'>CREADO</span></li>
        </ul>
        <strong>FALTA:</strong> Asignar el usuario a la BD (desde cPanel -> MySQL Databases)
        <br>O puedes usar este formulario para hacerlo automaticamente.
    </div>";
    
    echo "<form method='POST' style='background:#16213e;padding:20px;border-radius:10px;'>";
    echo "<h2>Configurar Base de Datos</h2>";
    
    echo "<label style='display:block;margin:10px 0;'>
        Contrasena MySQL ROOT (de cPanel):
        <input type='password' name='db_root_pass' value='' required
               style='width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;'>
        <small style='color:#888;'>La contrasena del usuario <code>nexusyl_root</code> que configuraste en cPanel</small>
    </label>";
    
    echo "<label style='display:block;margin:10px 0;'>
        Usuario BD nexusMK:
        <input type='text' name='db_user' value='nexusyl_nexusmk' 
               style='width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;'>
    </label>";
    
    echo "<label style='display:block;margin:10px 0;'>
        Contrasena Usuario nexusMK:
        <input type='text' name='db_pass' value='MkController2024!' 
               style='width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;'>
        <small style='color:#888;'>Contrasena para el usuario <code>nexusyl_nexusmk</code></small>
    </label>";
    
    echo "<label style='display:block;margin:10px 0;'>
        Nombre BD:
        <input type='text' name='db_name' value='nexusyl_nexusmk'
               style='width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;'>
    </label>";
    
    echo "<button type='submit' style='background:#00d4ff;color:#1a1a2e;padding:12px 30px;border:none;border-radius:5px;font-size:1.1em;cursor:pointer;margin-top:15px;'>
        Configurar Base de Datos
    </button>";
    echo "</form>";
}

echo "</body></html>";
