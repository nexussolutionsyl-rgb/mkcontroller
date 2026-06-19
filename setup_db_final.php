<?php
/**
 * Script FINAL para configurar BD nexusMK
 * Intenta multiples metodos para asignar privilegios y crear tablas
 * 
 * EJECUTAR: https://nexusmk.nexussolutionsyl.com/setup_db_final.php
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Setup Final nexusMK DB</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#1a1a2e;color:#e0e0e0;}
h1{color:#00d4ff;}
.success{color:#00ff88;background:#003322;padding:10px;border-radius:5px;margin:5px 0;}
.error{color:#ff4444;background:#330000;padding:10px;border-radius:5px;margin:5px 0;}
.info{color:#ffaa00;background:#332200;padding:10px;border-radius:5px;margin:5px 0;}
pre{background:#16213e;padding:10px;border-radius:5px;overflow-x:auto;}
code{color:#00d4ff;}
</style></head><body>";
echo "<h1>Configuracion Final Base de Datos nexusMK</h1>";

$db_host = 'localhost';
$db_name = 'nexusyl_nexusmk';
$db_user = 'nexusyl_nexusmk';
$db_pass = 'MkController2024!';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $root_pass = $_POST['root_pass'] ?? '';
    
    echo "<h2>Resultados:</h2>";
    
    // METODO 1: Intentar conectar como root con contrasena
    $connected = false;
    $conn = null;
    
    if (!empty($root_pass)) {
        try {
            $conn = new mysqli($db_host, 'nexusyl_root', $root_pass);
            if (!$conn->connect_error) {
                echo "<div class='success'>Metodo 1: Conexion como root con contrasena exitosa</div>";
                $connected = true;
            }
        } catch (Exception $e) {}
    }
    
    // METODO 2: Intentar conectar como root sin contrasena
    if (!$connected) {
        try {
            $conn = @new mysqli($db_host, 'nexusyl_root', '');
            if (!$conn->connect_error) {
                echo "<div class='success'>Metodo 2: Conexion como root sin contrasena exitosa</div>";
                $connected = true;
            }
        } catch (Exception $e) {}
    }
    
    // METODO 3: Intentar via mysql CLI
    if (!$connected) {
        $output = [];
        $returnVar = 0;
        $cmd = "mysql -u nexusyl_root -p'$root_pass' -e \"SELECT 1\" 2>&1";
        exec($cmd, $output, $returnVar);
        if ($returnVar === 0) {
            echo "<div class='success'>Metodo 3: CLI mysql como root funciona</div>";
            // Ejecutar GRANT via CLI
            $grantCmd = "mysql -u nexusyl_root -p'$root_pass' -e \"GRANT ALL PRIVILEGES ON $db_name.* TO '$db_user'@'localhost'; FLUSH PRIVILEGES;\" 2>&1";
            exec($grantCmd, $grantOut, $grantRet);
            if ($grantRet === 0) {
                echo "<div class='success'>GRANT ejecutado via CLI</div>";
                $connected = true;
            } else {
                echo "<div class='error'>Error GRANT via CLI: " . implode("\n", $grantOut) . "</div>";
            }
        } else {
            echo "<div class='error'>Metodo 3: CLI mysql no funciona</div>";
        }
    }
    
    // Si conectamos como root, hacer GRANT y CREATE TABLES
    if ($connected && $conn) {
        // GRANT
        $conn->query("GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'");
        $conn->query("FLUSH PRIVILEGES");
        echo "<div class='success'>Privilegios asignados a <code>$db_user</code> sobre <code>$db_name</code></div>";
        
        // Seleccionar BD
        $conn->select_db($db_name);
        
        // Crear tablas
        createTables($conn);
        
        $conn->close();
    } elseif ($connected) {
        // Si conectamos via CLI, ahora intentar con el usuario nexusmk
        try {
            $conn2 = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if (!$conn2->connect_error) {
                echo "<div class='success'>Conexion como <code>$db_user</code> exitosa despues de GRANT</div>";
                createTables($conn2);
                $conn2->close();
            }
        } catch (Exception $e) {
            echo "<div class='error'>Error conectando como $db_user: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>No se pudo conectar como root</div>";
        echo "<div class='info'>";
        echo "<p><strong>Instrucciones manuales:</strong></p>";
        echo "<ol>";
        echo "<li>Ve a cPanel -> MySQL Databases</li>";
        echo "<li>En la seccion 'Add User To Database':</li>";
        echo "<li>Selecciona Usuario: <code>nexusyl_nexusmk</code></li>";
        echo "<li>Selecciona BD: <code>nexusyl_nexusmk</code></li>";
        echo "<li>Marca TODOS LOS PRIVILEGIOS</li>";
        echo "<li>Haz clic en 'Make Changes'</li>";
        echo "</ol>";
        echo "<p>Luego vuelve a ejecutar este script.</p>";
        echo "</div>";
    }
    
} else {
    // Mostrar formulario
    echo "<div class='info'>
        <strong>Estado actual:</strong>
        <ul>
            <li>BD <code>nexusyl_nexusmk</code>: <span class='success'>CREADA</span></li>
            <li>Usuario <code>nexusyl_nexusmk</code>: <span class='success'>CREADO</span></li>
            <li>Usuario asignado a BD: <span class='error'>PENDIENTE</span></li>
        </ul>
        <p>Completa el formulario o sigue las instrucciones manuales.</p>
    </div>";
    
    echo "<form method='POST' style='background:#16213e;padding:20px;border-radius:10px;'>";
    echo "<h2>Configurar Base de Datos</h2>";
    
    echo "<label style='display:block;margin:10px 0;'>
        Contrasena MySQL ROOT (dejar vacio si no tiene):
        <input type='password' name='root_pass' value=''
               style='width:100%;padding:8px;background:#1a1a2e;color:#00d4ff;border:1px solid #00d4ff;border-radius:5px;margin-top:5px;'>
        <small style='color:#888;'>La contrasena del usuario <code>nexusyl_root</code> en cPanel</small>
    </label>";
    
    echo "<button type='submit' style='background:#00d4ff;color:#1a1a2e;padding:12px 30px;border:none;border-radius:5px;font-size:1.1em;cursor:pointer;margin-top:15px;'>
        Configurar Base de Datos
    </button>";
    echo "</form>";
    
    echo "<div class='info' style='margin-top:20px;'>";
    echo "<h3>Alternativa: Configuracion Manual</h3>";
    echo "<ol>";
    echo "<li>Ve a <a href='https://nexussolutionsyl.com/cpanel' style='color:#00d4ff;' target='_blank'>cPanel</a> -> MySQL Databases</li>";
    echo "<li>En 'Add User To Database', selecciona:</li>";
    echo "<ul>";
    echo "<li>Usuario: <code>nexusyl_nexusmk</code></li>";
    echo "<li>BD: <code>nexusyl_nexusmk</code></li>";
    echo "<li>Privilegios: TODOS</li>";
    echo "</ul>";
    echo "<li>Luego visita: <a href='https://nexusmk.nexussolutionsyl.com/create_tables_direct.php' style='color:#00d4ff;'>create_tables_direct.php</a></li>";
    echo "</ol>";
    echo "</div>";
}

function createTables($conn) {
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
    echo "<p>Luego probar: <a href='https://nexusmk.nexussolutionsyl.com/api/nexusmk/health' style='color:#00d4ff;'>/api/nexusmk/health</a></p>";
    echo "</div>";
}

echo "</body></html>";
