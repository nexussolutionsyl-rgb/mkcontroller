<?php
/**
 * Script para configurar BD nexusMK usando root password
 * Asigna privilegios, crea tablas e inserta datos iniciales
 * 
 * EJECUTAR: https://nexusmk.nexussolutionsyl.com/setup_db_root.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Setup nexusMK DB</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:900px;margin:40px auto;padding:20px;background:#1a1a2e;color:#e0e0e0;}
h1{color:#00d4ff;}
.success{color:#00ff88;background:#003322;padding:10px;border-radius:5px;margin:5px 0;}
.error{color:#ff4444;background:#330000;padding:10px;border-radius:5px;margin:5px 0;}
.info{color:#ffaa00;background:#332200;padding:10px;border-radius:5px;margin:5px 0;}
pre{background:#16213e;padding:10px;border-radius:5px;overflow-x:auto;}
code{color:#00d4ff;}
form{background:#16213e;padding:20px;border-radius:10px;margin:20px 0;}
input[type=password]{width:100%;padding:10px;margin:10px 0;border:1px solid #00d4ff;border-radius:5px;background:#1a1a2e;color:#e0e0e0;font-size:16px;}
input[type=submit]{background:#00d4ff;color:#1a1a2e;padding:12px 30px;border:none;border-radius:5px;font-size:16px;cursor:pointer;font-weight:bold;}
input[type=submit]:hover{background:#00b8e6;}
</style></head><body>";
echo "<h1>Configuracion Base de Datos nexusMK</h1>";

$db_host = 'localhost';
$db_name = 'nexusyl_nexusmk';
$db_user = 'nexusyl_nexusmk';
$db_pass = 'MkController2024!';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $root_pass = $_POST['root_pass'] ?? '';
    
    echo "<h2>Resultados:</h2>";
    
    if (empty($root_pass)) {
        echo "<div class='error'>ERROR: Debes proporcionar la contrasena root</div>";
        echo "</body></html>";
        exit;
    }
    
    // Conectar como root
    $conn = @new mysqli($db_host, 'nexusyl_root', $root_pass);
    if ($conn->connect_error) {
        echo "<div class='error'>ERROR: No se pudo conectar como root: " . $conn->connect_error . "</div>";
        echo "</body></html>";
        exit;
    }
    echo "<div class='success'>Conexion como root exitosa</div>";
    
    // PASO 1: Asignar privilegios al usuario nexusyl_nexusmk
    echo "<h3>Paso 1: Asignando privilegios...</h3>";
    
    $grant_queries = [
        "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'",
        "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'127.0.0.1'",
        "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'%'",
        "FLUSH PRIVILEGES"
    ];
    
    $grant_ok = true;
    foreach ($grant_queries as $q) {
        if ($conn->query($q)) {
            echo "<div class='success'>OK: " . substr($q, 0, 60) . "...</div>";
        } else {
            echo "<div class='info'>Info: " . substr($q, 0, 60) . "... -> " . $conn->error . "</div>";
        }
    }
    
    // PASO 2: Verificar que el usuario tiene acceso
    $conn->close();
    
    echo "<h3>Paso 2: Verificando acceso del usuario...</h3>";
    $conn2 = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn2->connect_error) {
        echo "<div class='error'>ERROR: El usuario $db_user NO tiene acceso a $db_name: " . $conn2->connect_error . "</div>";
        echo "</body></html>";
        exit;
    }
    echo "<div class='success'>Usuario $db_user tiene acceso a $db_name</div>";
    
    // PASO 3: Crear tablas
    echo "<h3>Paso 3: Creando tablas...</h3>";
    
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
    
    $table_ok = true;
    foreach ($tables_sql as $sql) {
        $table_name = '';
        if (preg_match('/CREATE TABLE.*?`(\w+)`/', $sql, $m)) {
            $table_name = $m[1];
        }
        if ($conn2->query($sql)) {
            echo "<div class='success'>Tabla '$table_name' creada/verificada correctamente</div>";
        } else {
            echo "<div class='error'>Error creando tabla '$table_name': " . $conn2->error . "</div>";
            $table_ok = false;
        }
    }
    
    // PASO 4: Insertar datos iniciales
    echo "<h3>Paso 4: Insertando datos iniciales...</h3>";
    
    // Admin user (password: admin123)
    $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
    $insert_admin = "INSERT IGNORE INTO `usuarios_nexusmk` 
        (`username`, `password`, `nombre`, `email`, `rol`, `permisos`, `activo`) 
        VALUES ('admin', '$admin_password', 'Administrador', 'admin@nexusmk.local', 'admin', 
        '{\"dispositivos\":true,\"wireguard\":true,\"firewall\":true,\"usuarios\":true,\"configuracion\":true}', 1)";
    
    if ($conn2->query($insert_admin)) {
        echo "<div class='success'>Usuario admin creado (admin/admin123)</div>";
    } else {
        echo "<div class='info'>Usuario admin ya existe o error: " . $conn2->error . "</div>";
    }
    
    // Default config
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
            echo "<div class='success'>Config '{$cfg[0]}' = '{$cfg[1]}'</div>";
        }
    }
    
    $conn2->close();
    
    echo "<h2 style='color:#00ff88;'>=== CONFIGURACION COMPLETADA EXITOSAMENTE ===</h2>";
    echo "<div class='success'>Base de datos '$db_name' configurada correctamente.</div>";
    echo "<div class='info'>Usuario: $db_user</div>";
    echo "<div class='info'>Password: $db_pass</div>";
    echo "<div class='info'>Base de datos: $db_name</div>";
    echo "<br><a href='/api/nexusmk/health' style='color:#00d4ff;font-size:18px;'>Probar /api/nexusmk/health</a>";
    echo "<br><br><a href='/api/nexusmk/db-info' style='color:#00d4ff;font-size:18px;'>Ver /api/nexusmk/db-info</a>";
    
} else {
    // Mostrar formulario
    echo "<form method='POST'>";
    echo "<h3>Configurar Base de Datos nexusMK</h3>";
    echo "<p>Se requiere la contrasena root de MySQL para asignar privilegios.</p>";
    echo "<label>Contrasena root de MySQL:</label>";
    echo "<input type='password' name='root_pass' placeholder='Ingresa la contrasena root' required>";
    echo "<input type='submit' value='Configurar Base de Datos'>";
    echo "</form>";
    echo "<div class='info'>";
    echo "<p><strong>Este script va a:</strong></p>";
    echo "<ol>";
    echo "<li>Conectar como root a MySQL</li>";
    echo "<li>Asignar privilegios al usuario <code>nexusyl_nexusmk</code> sobre <code>nexusyl_nexusmk</code></li>";
    echo "<li>Crear 6 tablas: usuarios_nexusmk, dispositivos_mikrotik, interfaces_wireguard, peers_wireguard, reglas_firewall, configuracion_general</li>";
    echo "<li>Insertar usuario admin (admin/admin123) y configuracion por defecto</li>";
    echo "</ol>";
    echo "</div>";
}

echo "</body></html>";
?>
